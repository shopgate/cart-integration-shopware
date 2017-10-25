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

namespace unit\Models\Export;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product|\PHPUnit_Framework_MockObject_MockObject */
    private $testClass;

    /**
     * Initializing main test class as mock
     */
    public function setUp()
    {
        $this->testClass = $this->getMockBuilder('Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product')
            ->disableOriginalConstructor()
            ->setMethods(
                array('getConfig', 'getCustomFieldsAsDescription', 'addDownloadsToDescription')
            )
            ->getMock();
    }

    /**
     * @param array  $articleData
     * @param string $configValue
     * @param string $expectedResult
     *
     * @dataProvider descriptionProvider
     */
    public function testPrepareDescription($articleData, $configValue, $expectedResult)
    {
        $fakeConfig = $this->getMockBuilder('Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config')
            ->disableOriginalConstructor()
            ->setMethods(array('getExportProductDescription'))
            ->getMock();

        $fakeConfig->expects($this->atLeastOnce())
            ->method('getExportProductDescription')
            ->will($this->returnValue($configValue));

        $this->testClass->expects($this->atLeastOnce())
            ->method('getConfig')
            ->will($this->returnValue($fakeConfig));

        $this->testClass->expects($this->once())
            ->method('getCustomFieldsAsDescription')
            ->will($this->returnArgument(0));

        $this->testClass->expects($this->once())
            ->method('addDownloadsToDescription')
            ->will($this->returnArgument(1));

        $result = $this->testClass->prepareDescription($articleData);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function descriptionProvider()
    {
        $articleData = array(
            'description'      => 'short',
            'description_long' => 'long',
            'sDownloads'       => '',
        );

        return array(
            'short description'          => array($articleData, 'short_desc', 'short'),
            'long description'           => array($articleData, 'desc', 'long'),
            'short and long description' => array($articleData, 'short_desc_and_desc', 'short<br /><br />long'),
            'long and short description' => array($articleData, 'desc_and_short_desc', 'long<br /><br />short'),
        );
    }
}
