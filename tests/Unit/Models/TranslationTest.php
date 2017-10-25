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

class TranslationTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation */
    private $subjectUnderTest;

    /** @var \Shopware_Components_Translation|\PHPUnit_Framework_MockObject_MockObject */
    private $translateComponentMock;

    public function setUp()
    {
        $this->translateComponentMock = $this->getMockBuilder('Shopware_Components_Translation')
            ->setMethods(array('read'))
            ->getMock();
        $this->subjectUnderTest       = new \Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation(
            $this->translateComponentMock,
            1,
            2
        );
    }

    public function testTranslate()
    {
        $this->translateComponentMock->expects($this->exactly(1))->method('read')->willReturn(array('name' => 'Color'));

        $this->assertEquals('Color', $this->subjectUnderTest->translate(1, 1, 'Farbe'));
    }

    public function testTranslateWithoutRepositoryResult()
    {
        $this->translateComponentMock->expects($this->exactly(2))->method('read')->willReturn(null);

        $this->assertEquals('Farbe', $this->subjectUnderTest->translate(1, 1, 'Farbe'));
    }

    public function testTranslateFallback()
    {
        $this->translateComponentMock->method('read')->willReturnOnConsecutiveCalls(
            null,
            array('name' => 'Color')
        );

        $this->assertEquals('Color', $this->subjectUnderTest->translate(1, 1, 'Farbe'));
    }
}
