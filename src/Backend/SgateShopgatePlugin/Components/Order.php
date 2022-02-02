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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Order
{
    /**
     * Order Status-ID
     * 2 => order was completed
     * 7 => order was shipped
     *
     * @var array
     */
    public static $shippingCompletedIds = array(2, 7);

    /**
     * Order Detail Status-ID
     * 2 => order was canceled
     * 4 => item was canceled
     *
     * @var array
     */
    public static $orderDetailsCanceledIds = array(2, 4);

    /**
     * Confirm shipping to shopgate
     *
     * @param string $orderNumber
     *
     * @throws Exception
     * @return boolean
     */
    public function confirmShipping($orderNumber)
    {
        /* @var $order \Shopware\CustomModels\Shopgate\Order */

        $order = Shopware()->Models()
            ->getRepository("\Shopware\CustomModels\Shopgate\Order")
            ->findOneBy(array("order_number" => $orderNumber));

        $orderId      = $order->getOrderId();
        $orderStatus  = $order->getOrder()->getOrderStatus()->getId();
        $trackingCode = $order->getOrder()->getTrackingCode();
        if (strlen($trackingCode) > 32) {
            ShopgateLogger::getInstance()->log(
                "TrackingCode '" . $trackingCode . "' is too long",
                ShopgateLogger::LOGTYPE_DEBUG
            );
            $trackingCode = '';
        }
        if (!in_array($orderStatus, self::$shippingCompletedIds)) {
            return true;
        }

        // If is shipped => return
        if ($order->getIsSentToShopgate()) {
            ShopgateLogger::getInstance()->log("Order is already shipped", ShopgateLogger::LOGTYPE_ERROR);

            return true;
        }

        $shippingComplete = false;
        $e                = null;

        try {
            $config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
            $config->reloadBySubShop($order->getOrder()->getShop());
            $builder              = new ShopgateBuilder($config);
            $oShopgateMerchantApi = $builder->buildMerchantApi();

            $oShopgateMerchantApi->addOrderDeliveryNote(
                $order->getOrderNumber(),
                ShopgateDeliveryNote::OTHER,
                $trackingCode,
                true
            );

            $shippingComplete = true;
        } catch (ShopgateMerchantApiException $e) {
            if ($e->getCode() == ShopgateMerchantApiException::ORDER_SHIPPING_STATUS_ALREADY_COMPLETED) {
                // all is fine
                $shippingComplete = true;
                $e                = null;
            }
        } catch (Exception $e) {
            ShopgateLogger::getInstance()->log(
                "Error from Merchant API: " . $e->getMessage(),
                ShopgateLogger::LOGTYPE_ERROR
            );
        }

        $order->setIsSentToShopgate($shippingComplete);

        Shopware()->Models()->flush($order);

        if ($e && defined("_SHOPGATE_API") && SHOPGATE_API) {
            throw $e;
        }

        return true;
    }

    /**
     * create a cancellation for this order number at shopgate
     *
     * if the while order has state ID:4 (Cancled) the whole order is also
     * cancled at shopgate.
     * If on any other state the items states are responsible. If one have
     * state 2 (Cancled) the whole item is also cancled at shopgate. Otherwise
     * the shopgate order item quantity will compare with the shopware item
     * quantity. if the last one is less then the other, a cancellation by
     * quantity is requestet to shopgate.
     *
     * @param string $orderNumber
     *
     * @return \Shopware\CustomModels\Shopgate\Order|null
     */
    public function cancelOrder($orderNumber)
    {
        /* @var $order \Shopware\CustomModels\Shopgate\Order */
        /* @var $shopgateOrder \ShopgateOrder */
        /* @var $shopwareOrder \Shopware\Models\Order\Order */

        ShopgateLogger::getInstance()->log(
            "Called method \"cancelOrder\" with order number #{$orderNumber}",
            ShopgateLogger::LOGTYPE_DEBUG
        );

        // fetch shopgate order model from database
        $order = Shopware()->Models()
            ->getRepository("\Shopware\CustomModels\Shopgate\Order")
            ->findOneBy(array("order_number" => $orderNumber));

        if (!$order) {
            ShopgateLogger::getInstance()->log(
                "Shopware order #{$orderNumber} not found.",
                ShopgateLogger::LOGTYPE_ERROR
            );

            return false;
        }

        $reportedCancellations = $order->getReportedCancellations();

        // get shopware order model from database
        $shopwareOrder = $order->getOrder();

        if (!$shopwareOrder) {
            return;
        }

        // get the shopgate order object
        $shopgateOrder = $order->getReceivedData();

        // check if the order needs to be synced
        $syncOrder = false;
        if ($shopgateOrder) {
            // check if all items have a shopgate order_item_id set
            foreach ($shopgateOrder->getItems() as $item) {
                if (!$item->getOrderItemId()) {
                    $syncOrder = true;
                    break;
                }
            }
        } else {
            // received data was not set, yet
            $syncOrder = true;
        }

        // reload the order from the shopgate server if the order is not available in serialized form or if it could not be loaded
        if ($syncOrder) {
            $shopgateOrder = $this->syncOrder($orderNumber);
        }

        $orderItemMap = $order->getOrderItemMap();
        if (!$orderItemMap) {
            $orderItemMap = array();
        }

        // build shopgate order item stack to access items by item number
        $itemStack = array();
        foreach ($shopgateOrder->getItems() as $item) {
            /* @var $item \ShopgateOrderItem */
            if (!empty($orderItemMap)) {
                $key = $item->getOrderItemId();
            } else {
                $key = $item->getItemNumber();
            }
            if ($key) {
                $itemStack[$key] = $item;
            }
        }

        $totalQty             = 0; // total qty of products
        $completeCancellation = 0; // cancelled qty
        $stack                = array(); // item stack for merchant api

        foreach ($shopwareOrder->getDetails() as $orderDetail) {
            ShopgateLogger::getInstance()->log(
                "cancelOrder:: processing detail with item number: #" . ($orderDetail->getArticleNumber()),
                ShopgateLogger::LOGTYPE_DEBUG
            );

            /* @var $orderDetail \Shopware\Models\Order\Detail */

            $cancelledItem = array( /* "order_item_id" => "", "quantity" => 0 */);

            // keep compatibility to older order imports, that have no order_item_id
            $cancellationItemIdentifierKey = !empty($orderItemMap)
                ? 'order_item_id'
                : 'item_number';
            $cancellationItemIdentifier    =
                !empty($orderItemMap)
                    ? $orderItemMap[$orderDetail->getId()]
                    : $orderDetail->getArticleNumber();

            // fix for shopgate coupons on older item imports
            if (empty($orderItemMap) && strtoupper(trim($cancellationItemIdentifier)) == 'COUPON') {
                // shopgate coupons can't be cancelled by item number since there is no real item with such an item number
                $cancellationItemIdentifier    = $itemStack[$cancellationItemIdentifier]->getOrderItemId();
                $cancellationItemIdentifierKey = 'order_item_id';
            }

            /* @var $item \ShopgateOrderItem */
            $item = $itemStack[$cancellationItemIdentifier];
            if (!$item) {
                continue;
            }

            $itemQty = $item->getQuantity();

            $orderInfo = $item->jsonDecode($item->getInternalOrderInfo(), true);
            if (!$orderInfo["purchasesteps"]) {
                $orderInfo["purchasesteps"] = 1;
            }
            $itemQty = $itemQty * $orderInfo["purchasesteps"];

            // reduce item quantity by already reported
            if (isset($reportedCancellations[$cancellationItemIdentifier])) {
                $itemQty -= $reportedCancellations[$cancellationItemIdentifier];
            }

            $totalQty += $itemQty;

            $qtyDiff = $itemQty - $orderDetail->getQuantity();
            // === qtyDiff Info ===
            // $qtyDiff = 0 => not changed
            // $qtyDiff > 0 => qty decreased
            // $qtyDiff < 0 => qty increased

            ShopgateLogger::getInstance()->log(
                "cancelOrder:: Detail-Status-Id: " . ($orderDetail->getStatus()->getId()),
                ShopgateLogger::LOGTYPE_DEBUG
            );
            if (
                (in_array(
                        $orderDetail->getStatus()->getId(),
                        self::$orderDetailsCanceledIds
                    ) || $qtyDiff == $itemQty) && $itemQty > 0
            ) {
                // complete cancellation
                $cancelledItem[$cancellationItemIdentifierKey] = $cancellationItemIdentifier;
                $cancelledItem["quantity"]                     = $itemQty;
                ShopgateLogger::getInstance()->log(
                    "cancelOrder:: Complete cancellation for detail!",
                    ShopgateLogger::LOGTYPE_DEBUG
                );
            } elseif ($qtyDiff > 0) {
                // partial cancellation
                $cancelledItem[$cancellationItemIdentifierKey] = $cancellationItemIdentifier;
                $cancelledItem["quantity"]                     = $qtyDiff;
                ShopgateLogger::getInstance()->log(
                    "cancelOrder:: Partial cancellation for detail -> reduced by a value of {$qtyDiff}",
                    ShopgateLogger::LOGTYPE_DEBUG
                );
            } elseif ($qtyDiff != 0) {

                // !! not supported case
                // !! changed quantity in shopware and it is more than shopgate order
                // TODO
                ShopgateLogger::getInstance()->log(
                    "cancelOrder:: Unsupported case -> item count has been raised by a value of {$qtyDiff}",
                    ShopgateLogger::LOGTYPE_DEBUG
                );
            }

            $cancelledItem["quantity"] = $cancelledItem["quantity"] / $orderInfo["purchasesteps"];

            if ($cancelledItem[$cancellationItemIdentifierKey]) {
                $completeCancellation += $cancelledItem["quantity"];
                $stack[]              = $cancelledItem;
            }
        }

        $cancelCompleteOrder = false;
        $cancelShippingCosts = false;

        ShopgateLogger::getInstance()->log(
            "cancelOrder:: Order-Status-Id #" . ($shopwareOrder->getOrderStatus()->getId()),
            ShopgateLogger::LOGTYPE_DEBUG
        );

        if ($shopwareOrder->getOrderStatus()->getId() == 4) {
            // Status ID 4 is cancled (See DB Table s_core_states)
            $cancelCompleteOrder = true;
        }

        if (!$cancelCompleteOrder && empty($stack)) {
            return null;
        }

        try {
            $config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
            $config->reloadBySubShop($order->getOrder()->getShop());
            $builder              = new ShopgateBuilder($config);
            $oShopgateMerchantApi = $builder->buildMerchantApi();
            $response             = $oShopgateMerchantApi->cancelOrder(
                $orderNumber,
                $cancelCompleteOrder,
                $stack,
                $cancelShippingCosts,
                "This cancellation was created in Shopware!"
            );

            foreach ($stack as $item) {
                $qty = 0;
                if (isset($reportedCancellations[$item["item_number"]])) {
                    $qty = $reportedCancellations[$item["item_number"]];
                }
                $qty                                         += $item["quantity"];
                $reportedCancellations[$item["item_number"]] = $qty;
            }
            $order->setReportedCancellations($reportedCancellations);

            if ($cancelCompleteOrder) {
                $order->setIsCancellationSentToShopgate(true);
            }

            Shopware()->Models()->flush($order);

            return $order;
        } catch (ShopgateMerchantApiException $e) {
            if ($e->getCode() == 222) { // already cancelled
                $order->setIsCancellationSentToShopgate(true);
                Shopware()->Models()->flush($order);
                $e = null;

                return $order;
            }
        } catch (Exception $e) {
        }

        if ($e) {
            $logModel = new \Shopware\Models\Log\Log();
            $logModel->setDate(new \DateTime("now"));
            $logModel->setIpAddress("");
            $logModel->setUserAgent('Unknown');
            $logModel->setText("Bestellung #{$orderNumber} konnte bei Shopgate nicht storniert werden!");
            $logModel->setType("backend");
            $logModel->setKey("Shopgate");
            $logModel->setUser("");
            $logModel->setValue4("");

            Shopware()->Models()->persist($logModel);
            Shopware()->Models()->flush($logModel);
        }

        return null;
    }

    /**
     * Reload the order from shopagte server and serialize it to
     * s_shopgate_order table field received_data
     *
     * @param string $orderNumber
     *
     * @return \ShopgateOrder
     */
    public function syncOrder($orderNumber)
    {
        /* @var $order \Shopware\Models\Order\Shopgate\Order */

        $order = Shopware()->Models()
            ->getRepository("\Shopware\CustomModels\Shopgate\Order")
            ->findOneBy(array("order_number" => $orderNumber));

        $config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        $config->reloadBySubShop($order->getOrder()->getShop());
        $builder              = new ShopgateBuilder($config);
        $oShopgateMerchantApi = $builder->buildMerchantApi();
        $shopgateOrders       = $oShopgateMerchantApi->getOrders(
            array("order_numbers" => array($orderNumber), "with_items" => true)
        );
        $shopgateOrders       = $shopgateOrders->getData();

        $shopgateOrder = array_shift($shopgateOrders);

        $order->setReceivedData($shopgateOrder);

        Shopware()->Models()->flush($order);

        return $shopgateOrder;
    }

    /**
     * @param $address
     * @param $subShop
     *
     * @return mixed[]
     */
    public function getShopgateAddressFromOrderAddress($address, $subShop)
    {
        $config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        $config->reloadBySubShop($subShop);

        $gender = $address->getSalutation() == ShopgatePluginShopware::MALE
            ? ShopgateCustomer::MALE
            : ShopgateCustomer::FEMALE;

        $country = Shopware()->Models()
            ->find('\Shopware\Models\Country\Country', $address->getCountry());

        $shopgateAddress = new ShopgateAddress();
        $shopgateAddress->setFirstName($address->getFirstname());
        $shopgateAddress->setLastName($address->getLastname());
        $shopgateAddress->setGender($gender);
        $shopgateAddress->setCompany($address->getCompany());
        $shopgateAddress->setStreet1(
            $address->getStreet() . (!$config->assertMinimumVersion('5.0.0')
                ?
                ' ' . $address->getStreetNumber()
                : '')
        );
        $shopgateAddress->setCity($address->getCity());
        $shopgateAddress->setZipcode($address->getZipCode());
        $shopgateAddress->setCountry($country->getIso());

        if (method_exists($address, 'getStateId')
            && $address->getStateId()
        ) {
            /** @var Shopware\Models\Country\State $state */
            $state = Shopware()->Models()->find('\Shopware\Models\Country\State', $address->getStateId());
            $shopgateAddress->setState($country->getIso() . '-' . $state->getShortCode());
        }

        if (method_exists($address, 'getPhone')) {
            $shopgateAddress->setPhone($address->getPhone());
        }

        return $shopgateAddress->toArray();
    }

    /**
     * @param Shopware\Models\Order\Order $order
     *
     * @return array
     */
    public function getOrderItemsFormatted($order)
    {
        $items = array();
        foreach ($order->getDetails() as $detail) {

            // mode == 2 => voucher, will be exported in external_coupon
            if ($detail->getMode() != 2) {
                /** @var \Shopware\Models\Order\Detail $detail */
                $shopgateItem = new ShopgateExternalOrderItem();
                $shopgateItem->setItemNumber($detail->getArticleId());
                $shopgateItem->setItemNumberPublic($detail->getArticleNumber());
                $shopgateItem->setQuantity((int)$detail->getQuantity());
                $shopgateItem->setname($detail->getArticleName());
                $shopgateItem->setUnitAmount(
                    Shopware()->Modules()->Articles()->sRound(
                        $detail->getPrice() / ((100 + (float)$detail->getTaxRate()) / 100)
                    )
                );
                $shopgateItem->setUnitAmountWithTax($detail->getPrice());
                $shopgateItem->setTaxPercent($detail->getTaxRate());
                $shopgateItem->setCurrency($order->getCurrency());
                array_push($items, $shopgateItem);
            }
        }

        return $items;
    }

    /**
     * @param Shopware\Models\Order\Order $order
     *
     * @return array
     */
    public function getOrderTaxFormatted($order)
    {
        $taxObjects = array();

        foreach ($order->getDetails() as $detail) {

            /** @var Shopware\Models\Tax\Tax $tax */
            $tax = $detail->getTax();

            if (!(bool)$tax->getId()) {
                continue;
            }

            $taxAmount = Shopware()->Modules()->Articles()->sRound(
                $detail->getPrice() - ($detail->getPrice() / ((100 + $detail->getTaxRate()) / 100))
            );
            if (empty($taxObjects[$tax->getId()])) {
                $taxObject = new ShopgateExternalOrderTax();
                $taxObject->setAmount($taxAmount);
                $taxObject->setLabel($tax->getName());
                $taxObject->setTaxPercent((float)$tax->getTax());
                $taxObjects[$tax->getId()] = $taxObject;
            } else {
                $taxObject = $taxObjects[$tax->getId()];
                $taxObject->setAmount($taxAmount + $taxObject->getAmount());
                $taxObjects[$tax->getId()] = $taxObject;
            }
        }

        return $taxObjects;
    }

    /**
     * @param Shopware\Models\Order\Order $order
     *
     * @return array
     */
    public function getDeliveryNotes($order)
    {
        // shopware has only one trackingcode per order
        $trackingCode  = $order->getTrackingCode();
        $deliveryNotes = array();
        if (!empty($trackingCode)) {
            $note = new ShopgateDeliveryNote();
            $note->setShippingServiceId($order->getDispatch()->getName());
            $note->setTrackingNumber($trackingCode);
        }

        return $deliveryNotes;
    }

    /**
     * @param Shopware\Models\Order\Order $order
     *
     * @return array
     */
    public function getCouponsFormatted($order)
    {
        $result = array();

        foreach ($order->getDetails() as $detail) {
            // modus 2 -> voucher
            if ($detail->getMode() == 2) {
                $code       = null;
                $getVoucher = Shopware()->Db()->fetchRow(
                    'SELECT ordercode, description, shippingfree FROM s_emarketing_vouchers WHERE id=?',
                    array(
                        $detail->getArticleId(),
                    )
                );

                if (!empty($getVoucher)) {
                    $code           = $getVoucher['ordercode'];
                    $name           = $getVoucher['description'];
                    $isShippingFree = $getVoucher['shippingfree'];
                } else {
                    $indivudalVoucher = Shopware()->Db()->fetchRow(
                        'SELECT vc.code, v.description, v.shippingfree
                                            FROM s_emarketing_vouchers as v
                                                LEFT JOIN s_emarketing_voucher_codes as vc ON vc.voucherID = v.id
                                            WHERE v.id=?',
                        array(
                            $detail->getArticleId(),
                        )
                    );

                    $code           = $indivudalVoucher['code'];
                    $name           = $indivudalVoucher['description'];
                    $isShippingFree = $getVoucher['shippingfree'];
                }

                if (!empty($code)) {
                    $externalCoupon = new ShopgateExternalCoupon();
                    $externalCoupon->setCode($code);
                    $externalCoupon->setName($name);
                    $externalCoupon->setAmount(abs($detail->getPrice()));
                    $externalCoupon->setIsFreeShipping((bool)$isShippingFree);
                    array_push($result, $externalCoupon);
                }
            }
        }

        return $result;
    }
}
