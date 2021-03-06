<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\UsersModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Zikula\Bundle\CoreInstallerBundle\Validator\Constraints\AuthenticateAdminLogin;
use Zikula\Bundle\HookBundle\Hook\ProcessHook;
use Zikula\Bundle\HookBundle\Hook\ValidationHook;
use Zikula\Bundle\HookBundle\Hook\ValidationProviders;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Event\GenericEvent;
use Zikula\UsersModule\AccessEvents;
use Zikula\UsersModule\AuthenticationMethodInterface\NonReEntrantAuthenticationMethodInterface;
use Zikula\UsersModule\AuthenticationMethodInterface\ReEntrantAuthenticationMethodInterface;
use Zikula\UsersModule\Constant;
use Zikula\UsersModule\Container\HookContainer;
use Zikula\UsersModule\Entity\UserEntity;
use Zikula\UsersModule\Exception\InvalidAuthenticationMethodLoginFormException;

class AccessController extends AbstractController
{
    /**
     * @Route("/login", options={"zkNoBundlePrefix"=1})
     * @param Request $request
     * @return string
     * @throws InvalidAuthenticationMethodLoginFormException
     */
    public function loginAction(Request $request)
    {
        if ($this->get('zikula_users_module.current_user')->isLoggedIn()) {
            return $this->redirectToRoute('zikulausersmodule_account_menu');
        }
        $returnUrl = $request->query->get('returnUrl', null);

        $authenticationMethodCollector = $this->get('zikula_users_module.internal.authentication_method_collector');
        $selectedMethod = $request->query->get('authenticationMethod', $request->getSession()->get('authenticationMethod', null));
        if (empty($selectedMethod) && count($authenticationMethodCollector->getActiveKeys()) > 1) {
            return $this->render('@ZikulaUsersModule/Access/authenticationMethodSelector.html.twig', [
                'collector' => $authenticationMethodCollector,
                'path' => 'zikulausersmodule_access_login'
            ]);
        } else {
            if (empty($selectedMethod) && count($authenticationMethodCollector->getActiveKeys()) == 1) {
                $selectedMethod = $authenticationMethodCollector->getActiveKeys()[0];
            }
            $request->getSession()->set('authenticationMethod', $selectedMethod); // save method to session for reEntrant needs
            if (!empty($returnUrl)) {
                $request->getSession()->set('returnUrl', $returnUrl);
            } // save returnUrl to session for reEntrant needs
        }
        $authenticationMethod = $authenticationMethodCollector->get($selectedMethod);
        $rememberMe = false;

        $this->get('event_dispatcher')->dispatch(AccessEvents::LOGIN_STARTED, new GenericEvent());

        if ($authenticationMethod instanceof NonReEntrantAuthenticationMethodInterface) {
            $form = $this->createForm($authenticationMethod->getLoginFormClassName());
            if (!$form->has('rememberme')) {
                throw new InvalidAuthenticationMethodLoginFormException();
            }
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $rememberMe = $data['rememberme'];
                $uid = $authenticationMethod->authenticate($data);
            } else {
                return $this->render($authenticationMethod->getLoginTemplateName(), [
                    'form' => $form->createView()
                ]);
            }
        } elseif ($authenticationMethod instanceof ReEntrantAuthenticationMethodInterface) {
            $uid = ($request->getMethod() == 'POST') ? Constant::USER_ID_ANONYMOUS : $authenticationMethod->authenticate(); // provide temp value for uid until form gives real value.
            $hasListeners = $this->get('event_dispatcher')->hasListeners(AccessEvents::LOGIN_FORM);
            $listenersHaveContent = $hasListeners ? !empty($this->get('event_dispatcher')->dispatch(AccessEvents::LOGIN_FORM, new GenericEvent())->getData()) : false;
            $hookBindings = $this->get('hook_dispatcher')->getBindingsFor('subscriber.users.ui_hooks.login_screen');
            if ($listenersHaveContent || count($hookBindings) > 0) {
                $form = $this->createForm('Zikula\UsersModule\Form\Type\DefaultLoginType', ['uid' => $uid]);
                $form->handleRequest($request);
                if ($form->isValid() && $form->isSubmitted()) {
                    $uid = $form->get('uid')->getData();
                    $rememberMe = $form->get('rememberme')->getData();
                } else {
                    return $this->render('@ZikulaUsersModule/Access/defaultLogin.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }
        } else {
            throw new \LogicException($this->__('Invalid authentication method.'));
        }
        $user = null;
        if (isset($uid)) {
            $user = $this->get('zikula_users_module.user_repository')->find($uid);
            if (isset($user)) {
                $validators = $this->get('event_dispatcher')->dispatch(AccessEvents::LOGIN_VALIDATE, new GenericEvent($user, [], new ValidationProviders()))->getData();
                $hook = new ValidationHook($validators);
                $this->get('hook_dispatcher')->dispatch(HookContainer::LOGIN_VALIDATE, $hook);
                $validators = $hook->getValidators();
                if (!$validators->hasErrors() && $this->get('zikula_users_module.helper.access_helper')->loginAllowed($user)) {
                    $this->get('event_dispatcher')->dispatch(AccessEvents::LOGIN_PROCESS, new GenericEvent($user));
                    $this->get('hook_dispatcher')->dispatch(HookContainer::LOGIN_PROCESS, new ProcessHook($user));
                    $event = new GenericEvent($user, ['authenticationMethod' => $selectedMethod]);
                    $this->get('event_dispatcher')->dispatch(AccessEvents::LOGIN_VETO, $event);
                    if (!$event->isPropagationStopped()) {
                        $returnUrlFromSession = urldecode($request->getSession()->get('returnUrl', $returnUrl));
                        $this->get('zikula_users_module.helper.access_helper')->login($user, $rememberMe);
                        $returnUrl = $this->dispatchLoginSuccessEvent($user, $selectedMethod, $returnUrlFromSession);
                    } else {
                        if ($event->hasArgument('flash')) {
                            $this->addFlash('danger', $event->getArgument('flash'));
                        }
                        $returnUrl = $event->getArgument('returnUrl');
                    }

                    return !empty($returnUrl) ? $this->redirect($returnUrl) : $this->redirectToRoute('home');
                }
            }
        }
        // login failed
        // @todo implement auto-register setting. If true, do so and proceed from here.
        $this->addFlash('error', $this->__('Login failed.'));
        $returnUrl = $this->dispatchLoginFailedEvent($user, $returnUrl, $authenticationMethod);

        return !empty($returnUrl) ? $this->redirect($returnUrl) : $this->redirectToRoute('home');
    }

    /**
     * @param UserEntity $user
     * @param $selectedMethod
     * @param $returnUrl
     * @return mixed
     */
    private function dispatchLoginSuccessEvent(UserEntity $user, $selectedMethod, $returnUrl)
    {
        $eventArgs = [
            'authenticationMethod' => $selectedMethod,
            'returnUrl' => $returnUrl,
        ];
        $defaultLastLogin = new \DateTime("1970-01-01 00:00:00");
        $actualLastLogin = $user->getLastlogin();
        if (empty($actualLastLogin) || $actualLastLogin == $defaultLastLogin) {
            $eventArgs['isFirstLogin'] = true;
        }
        $event = new GenericEvent($user, $eventArgs);
        $event = $this->get('event_dispatcher')->dispatch(AccessEvents::LOGIN_SUCCESS, $event);

        return $event->hasArgument('returnUrl') ? $event->getArgument('returnUrl') : $returnUrl;
    }

    /**
     * @param UserEntity|null $user
     * @param $returnUrl
     * @param $authenticationMethod
     * @return mixed
     */
    private function dispatchLoginFailedEvent(UserEntity $user = null, $returnUrl, $authenticationMethod)
    {
        $eventArgs = [
            'authenticationMethod' => $authenticationMethod,
            'returnUrl' => $returnUrl,
        ];
        $event = new GenericEvent($user, $eventArgs);
        $event = $this->get('event_dispatcher')->dispatch(AccessEvents::LOGIN_FAILED, $event);

        return $event->hasArgument('returnUrl') ? $event->getArgument('returnUrl') : $returnUrl;
    }

    /**
     * @Route("/logout/{returnUrl}", options={"zkNoBundlePrefix"=1})
     * @param Request $request
     * @param null $returnUrl
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function logoutAction(Request $request, $returnUrl = null)
    {
        $currentUser = $this->get('zikula_users_module.current_user');
        if ($currentUser->isLoggedIn()) {
            $uid = $currentUser->get('uid');
            $user = $this->get('zikula_users_module.user_repository')->find($uid);
            if ($this->get('zikula_users_module.helper.access_helper')->logout()) {
                $event = new GenericEvent($user, [
                    'authenticationMethod' => $request->getSession()->get('authenticationMethod'),
                    'uid' => $uid,
                ]);
                $this->get('event_dispatcher')->dispatch(AccessEvents::LOGOUT_SUCCESS, $event);
            } else {
                $this->addFlash('error', $this->__('Error! You have not been logged out.'));
            }
        }

        return isset($returnUrl) ? $this->redirect($returnUrl) : $this->redirectToRoute('home', ['_locale' => $this->container->getParameter('locale')]);
    }

    /**
     * This method is required when upgrading from <1.4.3 to >1.4.3 because the new ZAuth module is not yet available
     * when the site becomes disabled to perform the upgrade.
     *
     * @Route("/upgrade-login")
     * @deprecated remove at Core-2.0
     * @param Request $request
     * @return RedirectResponse
     */
    public function upgradeAdminLoginAction(Request $request)
    {
        $form = $this->createForm('Zikula\ZAuthModule\Form\Type\UnameLoginType');
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $userObject = [
                'username' => $form->get('uname')->getData(),
                'password' => $form->get('pass')->getData()
            ];
            $errors = $this->get('validator')->validate($userObject, [
                new AuthenticateAdminLogin()
            ]);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $uid = $this->get('doctrine.dbal.default_connection')->fetchColumn('SELECT uid FROM users WHERE uname= ?', [$userObject['username']], 0);
                $this->get('session')->clear();
                $this->get('session')->set('uid', $uid); // this 'logs in' the user
            }
        }

        return $this->redirectToRoute('upgrade');
    }
}
