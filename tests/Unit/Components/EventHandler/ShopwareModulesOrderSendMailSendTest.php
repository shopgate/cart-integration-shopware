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

namespace Unit\Components\EventHandler;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class ShopwareModulesOrderSendMailSendTest extends TestCase
{
    /**
     * @param bool      $sendingEnabled
     * @param bool|null $expectedReturn
     *
     * @dataProvider provideHandleFixtures
     */
    public function testHandle($sendingEnabled, $expectedReturn)
    {
        $eventHandler =
            new \Shopware_Plugins_Backend_SgateShopgatePlugin_Components_EventHandler_ShopwareModulesOrderSendMailSend(
                $sendingEnabled
            );

        $this->assertEquals($expectedReturn, $eventHandler->handle());
    }

    /**
     * @return array
     */
    public function provideHandleFixtures()
    {
        return array(
            'sending disabled, return false to avoid mails being sent'                   => array(
                false,
                false
            ),
            'sending enabled, return null to let the standard Shopware procedure happen' => array(
                true,
                null
            )
        );
    }
}
