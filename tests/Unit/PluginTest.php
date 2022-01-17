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

namespace Unit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class PluginTest extends TestCase
{
    /** @var \ShopgatePluginShopware */
    private $subjectUnderTest;

    public function set_up()
    {
        $this->subjectUnderTest = $this->getMockBuilder(
            'ShopgatePluginShopware'
        )
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    /**
     * @param string $expectedResult
     * @param string $paymentMethod
     *
     * @dataProvider providePaymentMethodsWithoutExplicitFallbackParameter
     */
    public function testGetShopPaymentMethodWithoutExplicitFallbackParameter($expectedResult, $paymentMethod)
    {
        $this->assertEquals($expectedResult, $this->subjectUnderTest->getShopPaymentMethodName($paymentMethod));
    }

    /**
     * @param string $expectedResult
     * @param string $paymentMethod
     * @param string $fallbackPaymentMethod
     *
     * @dataProvider providePaymentMethodsWithExplicitFallbackParameter
     */
    public function testGetShopPaymentMethodWithExplicitFallbackParameter(
        $expectedResult,
        $paymentMethod,
        $fallbackPaymentMethod
    ) {
        $this->assertEquals(
            $expectedResult,
            $this->subjectUnderTest->getShopPaymentMethodName($paymentMethod, $fallbackPaymentMethod)
        );
    }

    /**
     * @return array
     */
    public function providePaymentMethodsWithoutExplicitFallbackParameter()
    {
        return array(
            'unknown payment method' => array(
                \ShopgatePluginShopware::DEFAULT_PAYMENT_METHOD,
                'unknown payment method',
            ),
            'payment method shopgate' => array(
                'shopgate',
                \ShopgateOrder::SHOPGATE,
            ),
        );
    }

    /**
     * @return array
     */
    public function providePaymentMethodsWithExplicitFallbackParameter()
    {
        return array(
            'unknown payment method' => array(
                \ShopgatePluginShopware::CHECK_CART_PAYMENT_METHOD,
                'unknown payment method',
                \ShopgatePluginShopware::CHECK_CART_PAYMENT_METHOD,
            ),
        );
    }
}
