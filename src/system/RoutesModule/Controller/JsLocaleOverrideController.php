<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\RoutesModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Response\PlainResponse;

/**
 * Class JsLocaleOverrideController.
 */
class JsLocaleOverrideController extends AbstractController
{
    /**
     * @Route("/render-override.js")
     * @Method("GET")
     * @param Request $request Current request instance
     * @return PlainResponse
     */
    public function renderOverrideAction(Request $request)
    {
        $response = $this->render('ZikulaRoutesModule:JsLocaleOverride:renderOverride.js.twig', [], new PlainResponse());
        $response->headers->set('Content-Type', 'js');

        return $response;
    }
}
