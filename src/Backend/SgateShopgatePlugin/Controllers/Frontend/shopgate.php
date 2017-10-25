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

use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_Shopgate extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    public function getWhitelistedCSRFActions()
    {
        return array(
            'index',
            'ping',
            'cron',
            'addOrder',
            'updateOrder',
            'getCustomer',
            'registerCustomer',
            'getItems',
            'getItemsCsv',
            'getCategories',
            'getCategoriesCsv',
            'getLogFile',
            'clearLogFile',
            'clearCache',
            'checkCart',
            'redeemCoupons',
            'getSettings',
            'plugin',
        );
    }

    public function indexAction()
    {
        $this->View()->setTemplate();
        $this->redirect("Shopgate/plugin");
    }

    public function pingAction()
    {
        $action = 'ping';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function cronAction()
    {
        $action = 'cron';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function addOrderAction()
    {
        $action = 'add_order';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function updateOrderAction()
    {
        $action = 'update_order';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getCustomerAction()
    {
        $action = 'get_customer';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function registerCustomerAction()
    {
        $action = 'register_customer';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getItemsAction()
    {
        $action = 'get_items';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getItemsCsvAction()
    {
        $action = 'get_items_csv';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getCategoriesAction()
    {
        $action = 'get_categories';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getCategoriesCsvAction()
    {
        $action = 'get_categories_csv';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getReviewsAction()
    {
        $action = 'get_reviews';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getReviewsCsvAction()
    {
        $action = 'get_reviews_csv';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getPagesCsvAction()
    {
        $action = 'get_pages_csv';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getLogFileAction()
    {
        $action = 'get_log_file';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function clearLogFileAction()
    {
        $action = 'clear_log_file';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function clearCacheAction()
    {
        $action = 'clear_cache';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function checkCartAction()
    {
        $action = 'check_cart';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function redeemCouponsAction()
    {
        $action = 'redeem_coupons';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function getSettingsAction()
    {
        $action = 'get_settings';
        $this->Request()->setParam('action', $action);
        $this->Request()->setPost('action', $action);
        $this->pluginAction();
    }

    public function pluginAction()
    {
        define("_SHOPGATE_API", true);

        $this->View()->setTemplate();

        ### Shopinformation / Pluginconfiguration ###
        $shop   = Shopware()->Shop()->getId();
        $locale = Shopware()->Locale()->getRegion();

        if (empty($shop) || empty($locale)) {
            throw new Enlight_Exception('Plugin-Fehler! Shop oder Locale-ID leer!');
        }

        $config  = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        $builder = new ShopgateBuilder($config);
        $plugin  = new ShopgatePluginShopware($builder);

        $orderMailEventHandler =
            new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_EventHandler_ShopwareModulesOrderSendMailSend(
                $config->getSendOrderMail()
            );

        Shopware()->Events()->registerListener(
            new Enlight_Event_Handler_Default(
                'Shopware_Modules_Order_SendMail_Send',
                array($orderMailEventHandler, 'handle')
            )
        );

        $recreateMissingSGData = $this->Request()->getPost('recreate_missing_sg_data');
        if (!empty($recreateMissingSGData) && $recreateMissingSGData) {
            $orderNumbers = array();
            $sql          =
                "SELECT `o`.`ordernumber`, `o`.`transactionId` FROM `s_order` AS `o` LEFT JOIN `s_shopgate_orders` AS `so` ON(`o`.`id` = `so`.`orderID`) WHERE `o`.`remote_addr` LIKE 'shopgate.com.' AND `o`.`transactionId` LIKE '101%' AND `so`.`id` IS NULL";
            $query        = Shopware()->Db()->query($sql);
            while ($row = $query->fetch()) {
                // create a mapping from shopgate order number to shopware order number (link via shopgate order number as transactionId)
                $orderNumbers[substr($row['transactionId'], 0, 10)] = $row['ordernumber'];
            }

            if (!empty($orderNumbers)) {
                $oShopgateMerchantApi = $builder->buildMerchantApi();
                $sgOrderResponseObj   = $oShopgateMerchantApi->getOrders(
                    array(
                        "order_numbers" => array_keys($orderNumbers),
                        "with_items"    => true,
                    )
                ); // use the transactionId here because this is the shopgate order number
            }
            if (!empty($sgOrderResponseObj)) {
                /* @var $sgOrder ShopgateOrder */
                foreach ($sgOrderResponseObj->getData() as $sgOrder) {
                    // get the shopware order
                    Shopware()->Models()->clear();
                    $oShopwareOrder = Shopware()->Models()
                                                ->getRepository("\Shopware\Models\Order\Order")
                        // -> use the shopgate order number as transactionid as mapping to get the shopware order number
                                                ->findOneBy(array("number" => $orderNumbers[$sgOrder->getOrderNumber()]));

                    // create the database entry
                    $data = new \Shopware\CustomModels\Shopgate\Order();
                    $data->fromShopgateOrder($sgOrder);
                    $data->setOrder($oShopwareOrder);

                    Shopware()->Models()->persist($data);
                    Shopware()->Models()->flush();

                    unset($oShopwareOrder);
                    unset($data);
                }
            }
        }

        $plugin->handleRequest(
            array_merge(
                $this->Request()->getParams(),
                $this->Request()->getPost()
            )
        );

        exit;
    }
}
