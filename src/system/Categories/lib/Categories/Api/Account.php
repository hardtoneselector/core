<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv2.1 (or at your option, any later version).
 * @package Zikula
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */


class Categories_Api_Account extends Zikula_Api
{
    /**
     * Return an array of items to show in the your account panel.
     *
     * @return   array   indexed array of items
     */
    public function getall($args)
    {
        $items = array();

        // Create an array of links to return
        if (SecurityUtil::checkPermission('Categories::', '::', ACCESS_EDIT) && ModUtil::getVar('Categories', 'allowusercatedit')) {
            $referer = System::serverGetVar('HTTP_REFERER');
            if (strpos($referer, 'module=Categories') === false) {
                SessionUtil::setVar('categories_referer', $referer);
            }
            $items['0'] = array('url'     => ModUtil::url('Categories', 'user', 'edituser'),
                    'module'  => 'core',
                    'set'     => 'icons/large',
                    'title'   => $this->__('Categories manager'),
                    'icon'    => 'mydocuments.gif');
        }

        // Return the items
        return $items;
    }
}