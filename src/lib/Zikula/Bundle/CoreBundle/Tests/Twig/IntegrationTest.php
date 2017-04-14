<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\CoreBundle\Tests\Twig;

use Zikula\Bundle\CoreBundle\Twig\Extension\CoreExtension;
use Zikula\Common\Translator\IdentityTranslator;

class IntegrationTest extends \Twig_Test_IntegrationTestCase
{
    public function getExtensions()
    {
        return [
            new \Twig_Extension_Debug(),
            new CoreExtension(new IdentityTranslator())
        ];
    }

    public function getFixturesDir()
    {
        return dirname(__FILE__) . '/Fixtures/';
    }
}
