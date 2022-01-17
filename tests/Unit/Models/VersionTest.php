<?php

/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace unit\Models;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class VersionTest extends TestCase
{
    const SHOPWARE_VERSION = '4.3.6';

    /** @var \Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Version */
    private $subjectUnderTest;

    public function set_up()
    {
        $this->subjectUnderTest =
            new \Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Version(self::SHOPWARE_VERSION);
    }

    /**
     * @param bool   $expectedResult
     * @param string $versionToTest
     *
     * @dataProvider provideVersions
     */
    public function testAssertVersion($expectedResult, $versionToTest)
    {
        $this->assertEquals($expectedResult, $this->subjectUnderTest->assertMinimum($versionToTest));
    }

    /**
     * @return array
     */
    public function provideVersions()
    {
        return array(
            'newer version #1'   => array(
                false,
                '4.3.7',
            ),
            'newer version #2'   => array(
                false,
                '5.3.0',
            ),
            'exact same version' => array(
                true,
                self::SHOPWARE_VERSION,
            ),
            'older version #1'   => array(
                true,
                '4.3.5',
            ),
            'older version #2'   => array(
                true,
                '3.3.5',
            ),
        );
    }
}
