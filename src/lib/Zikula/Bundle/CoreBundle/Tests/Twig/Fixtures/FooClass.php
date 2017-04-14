<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\CoreBundle\Tests\Twig\Fixtures;

class FooClass
{
    public static function Bar($param = [])
    {
        return isset($param['a']) ? $param['a'] * 2 : 42;
    }
}