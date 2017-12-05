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

namespace Shopgate\Helpers;

class Cart
{
    /**
     * @param array $shippingMethods
     * @param array $paymentMethods
     *
     * @return array
     */
    public function adjustShippingCosts($shippingMethods, $paymentMethods)
    {
        if (empty($paymentMethods)) {
            return $shippingMethods;
        }

        /** @var \ShopgateShippingMethod $shippingMethod */
        foreach ($shippingMethods as $index => $shippingMethod) {
            /** @var \ShopgatePaymentMethod $currentPaymentMethod */
            $currentPaymentMethod = $this->getPaymentMethod($paymentMethods, $shippingMethod['paymentID']);
            if (!is_null($currentPaymentMethod)
                && !empty($currentPaymentMethod->getAmountWithTax())
                && empty($shippingMethod['surcharge_calculation'])
                && $shippingMethod['shipping_costs'] == $currentPaymentMethod->getAmountWithTax()
            ) {
                $shippingMethods[$index]['shipping_costs'] = 0.00;
            }
        }

        return $shippingMethods;
    }

    /**
     * @param array $paymentMethods
     * @param int   $id
     *
     * @return null|\ShopgatePaymentMethod
     */
    private function getPaymentMethod($paymentMethods, $id)
    {
        /** @var \ShopgatePaymentMethod $paymentMethod */
        foreach ($paymentMethods as $paymentMethod) {
            $paymentData = Shopware()->Db()->fetchOne(
                'SELECT id FROM s_core_paymentmeans WHERE name = ?',
                array($paymentMethod->getId())
            );
            if ($paymentData['id'] == $id) {
                return $paymentMethod;
            }
        }

        return null;
    }
}
