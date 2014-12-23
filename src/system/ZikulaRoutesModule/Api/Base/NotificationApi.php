<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 0.7.0 (http://modulestudio.de).
 */

namespace Zikula\RoutesModule\Api\Base;

use ModUtil;
use ServiceUtil;
use System;
use UserUtil;

use Zikula_AbstractApi;
use Zikula_View;

/**
 * Notification api base class.
 */
class NotificationApi extends Zikula_AbstractApi
{
    
    /**
     * List of notification recipients.
     *
     * @var array $recipients.
     */
    private $recipients = array();
    
    /**
     * Which type of recipient is used ("creator", "moderator" or "superModerator").
     *
     * @var string recipientType.
     */
    private $recipientType = '';
    
    /**
     * The entity which has been changed before.
     *
     * @var Zikula_EntityAccess entity.
     */
    private $entity = '';
    
    /**
     * Name of workflow action which is being performed.
     *
     * @var string action.
     */
    private $action = '';
    
    /**
     * Sends a mail to either an item's creator or a group of moderators.
     */
    public function process($args)
    {
        if (!isset($args['recipientType']) || !$args['recipientType']) {
            return false;
        }
    
        if (!isset($args['action']) || !$args['action']) {
            return false;
        }
    
        if (!isset($args['entity']) || !$args['entity']) {
            return false;
        }
    
        $this->recipientType = $args['recipientType'];
        $this->action = $args['action'];
        $this->entity = $args['entity'];
    
        $uid = UserUtil::getVar('uid');
    
        $this->collectRecipients();
    
        if (!count($this->recipients)) {
            return true;
        }
    
        if (!ModUtil::available('ZikulaMailerModule') || !ModUtil::loadApi('ZikulaMailerModule', 'user')) {
            return LogUtil::registerError($this->__('Could not inform other persons about your amendments, because the Mailer module is not available - please contact an administrator about that!'));
        }
    
        $result = $this->sendMails();
    
        $this->request->getSession()->del($this->name . 'AdditionalNotificationRemarks');
    
        return $result;
    }
    
    /**
     * Collects the recipients.
     */
    protected function collectRecipients()
    {
        $this->recipients = array();
    
        if ($this->recipientType == 'moderator' || $this->recipientType == 'superModerator') {
            $objectType = $this->entity['_objectType'];
            $moderatorGroupId = $this->getVar('moderationGroupFor' . $objectType, 2);
            if ($this->recipientType == 'superModerator') {
                $moderatorGroupId = $this->getVar('superModerationGroupFor' . $objectType, 2);
            }
    
            $moderatorGroup = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'get', array('gid' => $moderatorGroupId));
            foreach (array_keys($moderatorGroup['members']) as $uid) {
                $this->addRecipient($uid);
            }
        } elseif ($this->recipientType == 'creator' && isset($this->entity['createdUserId'])) {
            $creatorUid = $this->entity['createdUserId'];
    
            $this->addRecipient($creatorUid);
        }
    
        if (isset($args['debug']) && $args['debug']) {
            // add the admin, too
            $this->addRecipient(2);
        }
    }
    
    /**
     * Collects data for building the recipients array.
     *
     * @param $userId Id of treated user.
     */
    protected function addRecipient($userId)
    {
        $userVars = UserUtil::getVars($userId);
    
        $recipient = array(
            'name' => (isset($userVars['name']) && !empty($userVars['name']) ? $userVars['name'] : $userVars['uname']),
            'email' => $userVars['email']
        );
        $this->recipients[] = $recipient;
        return $recipient;
    }
    
    /**
     * Performs the actual mailing.
     */
    protected function sendMails()
    {
        $objectType = $this->entity['_objectType'];
        $siteName = System::getVar('sitename');
    
        $view = Zikula_View::getInstance('ZikulaRoutesModule');
        $templateType = $this->recipientType == 'creator' ? 'Creator' : 'Moderator';
        $template = 'Email/notify' . ucfirst($objectType) . $templateType .  '.tpl';
    
        $mailData = $this->prepareEmailData();
    
        // send one mail per recipient
        $totalResult = true;
        foreach ($this->recipients as $recipient) {
            if (!isset($recipient['username']) || !$recipient['username']) {
                continue;
            }
            if (!isset($recipient['email']) || !$recipient['email']) {
                continue;
            }
    
            $view->assign('recipient', $recipient)
                 ->assign('mailData', $mailData);
    
            $mailArgs = array(
                'fromname' => $siteName,
                'toname' => $recipient['name'],
                'toaddress' => $recipient['email'],
                'subject' => $this->getMailSubject(),
                'body' => $view->fetch($template),
                'html' => true
            );
    
            $totalResult &= ModUtil::apiFunc('ZikulaMailerModule', 'user', 'sendmessage', $mailArgs);
        }
    
        return $totalResult;
    }
    
    protected function getMailSubject()
    {
        $mailSubject = '';
        if ($this->recipientType == 'moderator' || $this->recipientType == 'superModerator') {
            if ($this->action == 'submit') {
                $mailSubject = $this->__('New content has been submitted');
            } else {
                $mailSubject = $this->__('Content has been updated');
            }
        } elseif ($this->recipientType == 'creator') {
            $mailSubject = $this->__('Your submission has been updated');
        }
    
        return $mailSubject;
    }
    
    protected function prepareEmailData()
    {
        $serviceManager = ServiceUtil::getManager();
        $workflowHelper = $serviceManager->get('zikularoutesmodule.workflow_helper');
    
        $objectType = $this->entity['_objectType'];
        $state = $this->entity['workflowState'];
        $stateInfo = $workflowHelper->getStateInfo($state);
    
        $remarks = $this->request->getSession()->get($this->name . 'AdditionalNotificationRemarks', '');
    
        $urlArgs = $this->entity->createUrlArgs();
        $displayUrl = '';
        $editUrl = '';
    
        if ($this->recipientType == 'moderator' || $this->recipientType == 'superModerator') {
            $urlArgs['lct'] = 'admin';
            $displayUrl = $serviceManager->get('router')->generate('zikularoutesmodule_' . strtolower($objectType) . '_display', $urlArgs, true);
            $editUrl = $serviceManager->get('router')->generate('zikularoutesmodule_' . strtolower($objectType) . '_edit', $urlArgs, true);
        } elseif ($this->recipientType == 'creator') {
            // nothing to do as no user controller is available
        }
    
        $emailData = array(
            'name' => $this->entity->getTitleFromDisplayPattern(),
            'newState' => $stateInfo['text'],
            'remarks' => $remarks,
            'displayUrl' => $displayUrl,
            'editUrl' => $editUrl
        );
    
        return $emailData;
    }
}
