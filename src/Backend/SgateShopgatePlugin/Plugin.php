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

use Shopgate\Helpers\Cart as CartHelper;
use Shopware\Models\Order\Order;

require_once __DIR__ . '/vendor/autoload.php';

class ShopgatePluginShopware extends ShopgatePlugin
{
    const MALE   = "mr";
    const FEMALE = "ms";
    const PAYMORROW_ORDERS_TABLE = 'pi_paymorrow_orders';
    const DEFAULT_PAYMENT_METHOD = 'mobile_payment';
    const CHECK_CART_PAYMENT_METHOD = 'prepayment';
    const ORDER_STATUS_CANCELED             = -1;
    const ORDER_STATUS_OPEN                 = 0;
    const ORDER_STATUS_IN_WORK              = 1;
    const ORDER_STATUS_FULLY_COMPLETED      = 2;
    const ORDER_STATUS_PARTIALLY_COMPLETED  = 3;
    const ORDER_STATUS_CANCELED_REJECTED    = 4;
    const ORDER_STATUS_READY_FOR_DELIVERY   = 5;
    const ORDER_STATUS_PARTIAL_DELIVERED    = 6;
    const ORDER_STATUS_DELIVERED_COMPLETELY = 7;
    const ORDER_STATUS_CLARIFICATION_NEEDED = 8;
    const ORDER_PAYMENT_STATUS_PARTIALLY_CHARGED      = 9;
    const ORDER_PAYMENT_STATUS_FULLY_CHARGED          = 10;
    const ORDER_PAYMENT_STATUS_PARTLY_PAID            = 11;
    const ORDER_PAYMENT_STATUS_FULLY_PAID             = 12;
    const ORDER_PAYMENT_STATUS_FIRST_REMINDER         = 13;
    const ORDER_PAYMENT_STATUS_SECOND_REMINDER        = 14;
    const ORDER_PAYMENT_STATUS_THIRD_REMINDER         = 15;
    const ORDER_PAYMENT_STATUS_COLLECTION             = 16;
    const ORDER_PAYMENT_STATUS_OPEN                   = 17;
    const ORDER_PAYMENT_STATUS_RESERVED               = 18;
    const ORDER_PAYMENT_STATUS_DELAYED                = 19;
    const ORDER_PAYMENT_STATUS_RECREDIT               = 20;
    const ORDER_PAYMENT_STATUS_VERIFICATION_NECESSARY = 21;

    /**
     * @var int
     */
    protected $langId;

    /**
     * @var string
     */
    protected $defaultCustomerGroupKey;

    /**
     * @var int
     */
    protected $defaultCustomerGroupId;

    /**
     * @var sSystem
     */
    protected $system;

    /**
     * @var $locale Shopware\Models\Shop\Locale
     */
    protected $locale;

    /**
     * @var \Shopware\Models\Shop\Shop
     */
    private $shop;

    /**
     * @var Shopware_Components_Translation
     */
    protected $translation;

    /**
     *
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config
     */
    protected $config;

    /**
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export
     */
    protected $exportComponent = null;

    /**
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Settings
     */
    protected $settingsComponent = null;

    /**
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Category
     */
    protected $categoryComponent = null;

    /** @var Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation */
    protected $translationModel;

    /**
     * temporarily until csv export model exists
     *
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product
     */
    protected $exportModel = null;

    /** @var Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Import_Customer */
    protected $customerImport = null;

    /** @var CartHelper */
    protected $cartHelper;

    /*
     * merged array form post and params of enlight request
     */
    protected $params = array();

    protected $iMaxCategoryPosition = 0;

    /**
     * @var ShopgateOrderItem[] List of line items that could not be added to the basket during add_order
     */
    private $nonInsertableOrderItems = array();

    public function startup()
    {
        if (!defined("SHOPGATE_PLUGIN_VERSION")) {
            define("SHOPGATE_PLUGIN_VERSION", Shopware()->Plugins()->Backend()->SgateShopgatePlugin()->getVersion());
        }

        $request      = Shopware()->Front()->Request();
        $this->params = array_merge(
            $request->getParams(),
            $request->getPost()
        );

        // allow removal of debug files
        if (!empty($this->params['sg_cleanup_file'])) {
            $t = str_replace('.php', '', $this->params['sg_cleanup_file']);
            if ($t == 'pmn'
                || $t == 'cleanup_files'
                || $t == 'chkconfig'
            ) {
                unlink(dirname(__FILE__) . "/{$this->params['sg_cleanup_file']}.php");
            } elseif ($this->params['sg_cleanup_file'] == 'all') {
                unlink(dirname(__FILE__) . "/pmn.php");
                unlink(dirname(__FILE__) . "/chkconfig.php");
                unlink(dirname(__FILE__) . "/cleanup_files.php");
            }
            unset($this->params['sg_cleanup_file']);
        }

        $this->config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();

        $this->shop        = Shopware()->Models()
            ->find("Shopware\Models\Shop\Shop", Shopware()->Shop()->getId());
        $this->system      = Shopware()->System();
        $this->locale      = Shopware()->Models()
            ->find("Shopware\Models\Shop\Shop", Shopware()->Shop()->getId())->getLocale();

        if ($this->config->assertMinimumVersion('5.6')) {
            $container         = Shopware()->Container();
            $connection        = Shopware()->Container()->get('dbal_connection');
            $this->translation = new Shopware_Components_Translation($connection, $container);
        } else {
            $this->translation = new Shopware_Components_Translation();
        }

        $this->langId      = $this->shop->getLocale()->getId();

        $customerGroupKey              = Shopware()->Shop()->getCustomerGroup()->getKey();
        $customerGroupId               = Shopware()->Shop()->getCustomerGroup()->getId();
        $this->defaultCustomerGroupKey = !is_null($customerGroupKey)
            ? $customerGroupKey
            : 'EK';
        $this->defaultCustomerGroupId  = !is_null($customerGroupId)
            ? $customerGroupId
            : 1;
        $this->settingsComponent       =
            new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Settings($this->defaultCustomerGroupKey);
        $this->customerImport          =
            new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Import_Customer($this->config);

        $fallbackShop           = Shopware()->Shop()->getFallback();
        $this->translationModel = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation(
            $this->translation,
            Shopware()->Shop()->getId(),
            !empty($fallbackShop)
                ? $fallbackShop->getId()
                : null
        );

        $version = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Version();
        if ($version->assertMinimum('5.3.0')) {
            $articleSortModel = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_Article(
                Shopware()->Container()->get('shopware_storefront.custom_sorting_service'),
                Shopware()->Container()->get('shopware_search.store_front_criteria_factory'),
                Shopware()->Container()->get('shopware_searchdbal.dbal_query_builder_factory'),
                Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext(),
                new Enlight_Controller_Request_RequestHttp()
            );
        } else {
            $articleSortModel = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_ArticleLegacy(
                $this->system->sCONFIG['defaultListingSorting']
            );
        }

        $this->exportComponent =
            new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export(
                ShopgateLogger::getInstance()->getLoggingStrategy(),
                $articleSortModel,
                $this->config->getRootCategory(),
                $this->params
            );

        $this->cartHelper = new CartHelper();

        return true;
    }


    ############################################################################
    ## LOADERS
    ############################################################################

    protected function getPluginInfoLoaders()
    {
        return array(
            "_loadShopwareVersion",
            /** @see ShopgatePluginShopware::_loadShopwareVersion */
            "_loadShopwareRevision",
            /** @see ShopgatePluginShopware::_loadShopwareRevision */
        );
    }

    protected function getAddOrderLoaders()
    {
        return array(
            'insertOrder',
            /** @see ShopgatePluginShopware::insertOrder */
            'insertOrderCustomer',
            /** @see ShopgatePluginShopware::insertOrderCustomer */
            'insertOrderDeliveryAddress',
            /** @see ShopgatePluginShopware::insertOrderDeliveryAddress */
            'insertOrderInvoiceAddress',
            /** @see ShopgatePluginShopware::insertOrderInvoiceAddress */
            'insertOrderItems',
            /** @see ShopgatePluginShopware::insertOrderItems */
            'insertOrderExternalCoupons',
            /** @see ShopgatePluginShopware::insertOrderExternalCoupons */
            'insertOrderPaymentCosts',
            /** @see ShopgatePluginShopware::insertOrderPaymentCosts */
            'setOrderPayment',
            /** @see ShopgatePluginShopware::setOrderPayment */
        );
    }

    protected function getAfterAddOrderLoaders()
    {
        return array(
            'setOrderInternalComment',
            /** @see ShopgatePluginShopware::setOrderInternalComment */
            'setOrderCustomFields',
            /** @see ShopgatePluginShopware::setOrderCustomFields */
            'setOrderClear',
            /** @see ShopgatePluginShopware::setOrderClear */
            'setOrderStatus',
            /** @see ShopgatePluginShopware::setOrderStatus */
            'insertPlentyOrderData',
            /** @see ShopgatePluginShopware::insertPlentyOrderData */
            'insertShopgateOrderData',
            /** @see ShopgatePluginShopware::insertShopgateOrderData */
            'insertPaymorrowOrderData',
            /** @see ShopgatePluginShopware::insertPaymorrowOrderData */
            'insertPayolutionOrderData',
            /** @see ShopgatePluginShopware::insertPayolutionOrderData */
            'insertPaypalPlusOrderData',
            /** @see ShopgatePluginShopware::insertPaypalPlusOrderData */
            'insertPaypalUnifiedOrderData',
            /** @see ShopgatePluginShopware::insertPaypalUnifiedOrderData */
            'insertSepaOrderData',
            /** @see ShopgatePluginShopware::insertSepaOrderData */
            'insertAmazonPaymentsOrderData',
            /** @see ShopgatePluginShopware::insertAmazonPaymentsOrderData */
        );
    }


    ############################################################################
    ## CRON                                                                   ##
    ############################################################################

    public function cron($jobname, $params, &$message, &$errorcount)
    {
        switch ($jobname) {
            case "set_shipping_completed":
                $this->setOrderShippingCompleted($message, $errorcount);
                // 				Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Cron::checkOrderStatus( $message, $errorcount );
                break;
            case "cancel_orders":
                $this->log("> Run job {$jobname}", ShopgateLogger::LOGTYPE_DEBUG);
                $this->cronCancelOrder($message, $errorcount);
                break;
            // 			case "clean_orders":
            // 				Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Cron::cleanupOrders( $message, $errorcount );
            // 				break;
            default:
                throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CRON_UNSUPPORTED_JOB);
        }
    }

    public function setOrderShippingCompleted(&$message, &$errorcount)
    {
        $shippingCompletedIds = Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Order::$shippingCompletedIds;
        $shippingCompletedIds = implode(",", $shippingCompletedIds);

        $sql = "SELECT DISTINCT
				so.shopgate_order_number
				FROM s_shopgate_orders so
				JOIN s_order o ON (so.orderID = o.id)
				WHERE so.is_sent_to_shopgate = 0
				  AND o.status IN ({$shippingCompletedIds})";

        $query = Shopware()->Db()->query($sql);
        while ($row = $query->fetch()) {
            $this->log(
                "Try to set shipping completed for order with shopgate-order-number #{$row['shopgate_order_number']}",
                ShopgateLogger::LOGTYPE_DEBUG
            );
            try {
                $oh = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Order();
                $oh->confirmShipping($row["shopgate_order_number"]);
                $message .= "Setting \"shipping complete\" for shopgate-order #{$row["shopgate_order_number"]} successfully completed\n";
            } catch (Exception $e) {
                $errorcount++;
                $message .= "Error while setting \"shipping complete\" for shopgate-order #{$row["shopgate_order_number"]}\n";
            }
        }
    }

    /**
     *
     * @param string $message
     * @param int    $errorcount
     */
    protected function cronCancelOrder(&$message, &$errorcount)
    {
        $sql = "SELECT DISTINCT
				so.shopgate_order_number
				FROM s_shopgate_orders so
				JOIN s_order o ON (so.orderID = o.id)
				WHERE so.is_cancellation_sent_to_shopgate = 0
				  AND DATE(o.ordertime) > ADDDATE(NOW(), INTERVAL -4 WEEK)";

        $query = Shopware()->Db()->query($sql);
        $oh    = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Order();

        while ($row = $query->fetch()) {
            $this->log(
                "Checking cancellation status for shopgate-order-number #{$row['shopgate_order_number']}",
                ShopgateLogger::LOGTYPE_DEBUG
            );
            try {
                if ($oh->cancelOrder($row["shopgate_order_number"])) {
                    $message .= "Cancelling order for shopgate-order #{$row["shopgate_order_number"]} successfully completed\n";
                    // 				} else {
                    // 					$message .= "Cancelling shopgate-order #{$row["shopgate_order_number"]} failed\n";
                }
            } catch (Exception $e) {
                $errorcount++;
                $message .= "Error while cancelling shopgate-order #{$row["shopgate_order_number"]}\n";
            }
        }
    }


    ############################################################################
    ## PLUGIN-INFOS                                                           ##
    ############################################################################

    public function createPluginInfo()
    {
        $aInfos = array();
        $aInfos = $this->executeLoaders($this->getPluginInfoLoaders(), $aInfos);

        return $aInfos;
    }

    protected function _loadShopwareVersion($aInfos)
    {
        $aInfos["version"] = Shopware()->Config()->version;

        return $aInfos;
    }

    protected function _loadShopwareRevision($aInfos)
    {
        $aInfos["revision"] = Shopware()->Config()->revision;

        return $aInfos;
    }

    ############################################################################
    ## SHOPGATE-CONNECT + REGISTER_CUSTOMER                                   ##
    ############################################################################

    /**
     * Checks if a user exists with the given creditentionls and returns a user with all addresses on success
     *
     * @param string $user
     * @param string $pass
     *
     * @return ShopgateCustomer
     * @throws ShopgateLibraryException
     */
    public function getCustomer($user, $pass)
    {
        /* @var $oCustomer \Shopware\Models\Customer\Customer */
        /* @var $oBilling \Shopware\Models\Customer\Billing */
        /* @var $oShipping \Shopware\Models\Customer\Shipping */
        $userId = null;

        if (!$this->config->assertMinimumVersion('4.1')) {
            $sql      = "SELECT id, password FROM `s_user` WHERE email = ?";
            $userData = Shopware()->Db()->fetchRow($sql, array($user));

            $hashedPassword = $userData["password"];

            $validPass = md5($pass) === $hashedPassword;
            $userId    = $userData["id"];
        } else {
            $sql      = "SELECT id, password, encoder, customergroup FROM `s_user` WHERE email = ? AND accountmode = 0";
            $userData = Shopware()->Db()->fetchRow($sql, array($user));
            if (!empty($userData['id'])) {
                $encoder        = strtolower($userData["encoder"]);
                $hashedPassword = $userData["password"];

                $validPass = Shopware()->PasswordEncoder()->isPasswordValid($pass, $hashedPassword, $encoder);
                $userId    = $userData["id"];
            }
        }

        if (!$validPass || !$userId) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD);
        }
        $oCustomer        = Shopware()->Models()->find("\Shopware\Models\Customer\Customer", $userId);
        $customerGroupKey = $userData['customergroup'];
        $oBilling         = $this->config->assertMinimumVersion('5.5.0')
            ? $oCustomer->getDefaultBillingAddress()
            : $oCustomer->getBilling();
        $oShipping        = $this->config->assertMinimumVersion('5.5.0')
            ? $oCustomer->getDefaultShippingAddress()
            : $oCustomer->getShipping();

        if (!$oShipping && $oBilling) {
            $oShipping = $oBilling;
        }

        $aAddresses = array();
        $userData   = new ShopgateCustomer();

        $customerNumber = $this->config->assertMinimumVersion('5.2.0')
            ? $oCustomer->getNumber()
            : $oBilling->getNumber();

        $userData->setCustomerId($oCustomer->getId());
        $userData->setCustomerNumber($customerNumber);

        $userData->setGender(
            $oBilling->getSalutation() == self::MALE
                ? ShopgateCustomer::MALE
                : ShopgateCustomer::FEMALE
        );
        $userData->setFirstName($oBilling->getFirstName());
        $userData->setLastName($oBilling->getLastName());

        $userData->setMail($oCustomer->getEmail());
        $userData->setPhone($oBilling->getPhone());

        $sql       = "SELECT id, description FROM `s_core_customergroups` WHERE `groupkey` = ?";
        $groupData = Shopware()->Db()->fetchRow($sql, array($customerGroupKey));

        $customerGroup = new ShopgateCustomerGroup();
        $customerGroup->setId($groupData['id']);
        $customerGroup->setName($groupData['description']);
        $userData->setCustomerGroups(array($customerGroup));

        $userData->setTaxClassId($groupData['id']);
        $userData->setTaxClassKey($customerGroupKey);

        $token = Shopware()->Db()->fetchOne(
            "SELECT token FROM s_shopgate_customer WHERE userID=?",
            array(
                $oCustomer->getId(),
            )
        );

        if (empty($token)) {
            $data = new \Shopware\CustomModels\Shopgate\Customer();
            $data->setTokenByCustomer($oCustomer);
            $data->setCustomerId($oCustomer->getId());

            Shopware()->Models()->persist($data);
            Shopware()->Models()->flush($data);
            $token = $data->getToken();
        }

        $userData->setCustomerToken($token);

        $birthday = $this->config->assertMinimumVersion('5.2.0')
            ? $oCustomer->getBirthday()
            : $oBilling->getBirthday();

        if ($birthday
            && $birthday instanceof \DateTime
        ) {
            $userData->setBirthday($birthday->format('Y-m-d'));
        }

        // Invoice Address
        $oInvoiceAddress = new ShopgateAddress();
        $oInvoiceAddress->setAddressType(ShopgateAddress::INVOICE);
        $oInvoiceAddress->setId($oBilling->getId());
        $oInvoiceAddress->setGender(
            $oBilling->getSalutation() == self::MALE
                ? ShopgateCustomer::MALE
                : ShopgateCustomer::FEMALE
        );
        $oInvoiceAddress->setFirstName($oBilling->getFirstName());
        $oInvoiceAddress->setLastName($oBilling->getLastName());
        $oInvoiceAddress->setCompany($oBilling->getCompany());
        $oInvoiceAddress->setStreet1(
            $oBilling->getStreet() .
            (!$this->config->assertMinimumVersion('5.0.0')
                ? ' ' . $oBilling->getStreetNumber()
                : ''
            )
        );
        if ($this->config->assertMinimumVersion('5.0.0')
            && $oBilling->getAdditionalAddressLine1()
        ) {
            $oInvoiceAddress->setStreet2($oBilling->getAdditionalAddressLine1());
        }
        $oInvoiceAddress->setZipcode($oBilling->getZipCode());
        $oInvoiceAddress->setCity($oBilling->getCity());
        $oInvoiceAddress->setBirthday($userData->getBirthday());

        $countryId = $this->config->assertMinimumVersion('5.5.0')
            ? $oBilling->getCountry()->getId()
            : $oBilling->getCountryId();

        $oCountry = Shopware()->Models()->find('\Shopware\Models\Country\Country', $countryId);
        $oInvoiceAddress->setCountry($oCountry->getIso());
        $oInvoiceAddress->setPhone($oBilling->getPhone());

        if ($this->config->assertMinimumVersion('5.5.0')) {
            $stateId = $oBilling->getState()
                ? $oBilling->getState()->getId()
                : null;
        } else {
            $stateId = $oBilling->getStateId();
        }

        if ($stateId) {
            /** @var Shopware\Models\Country\State $state */
            $state = Shopware()->Models()->find('\Shopware\Models\Country\State', $stateId);
            $oInvoiceAddress->setState($oCountry->getIso() . '-' . $state->getShortCode());
        }

        $aAddresses[] = $oInvoiceAddress;

        // Shipping Address
        $oShippingAddress = new ShopgateAddress();
        $oShippingAddress->setAddressType(ShopgateAddress::DELIVERY);
        $oShippingAddress->setId($oShipping->getId());
        $oShippingAddress->setGender(
            $oShipping->getSalutation() == self::MALE
                ? ShopgateCustomer::MALE
                : ShopgateCustomer::FEMALE
        );
        $oShippingAddress->setFirstName($oShipping->getFirstName());
        $oShippingAddress->setLastName($oShipping->getLastName());
        $oShippingAddress->setCompany($oShipping->getCompany());
        $oShippingAddress->setStreet1(
            $oShipping->getStreet()
            . (!$this->config->assertMinimumVersion('5.0.0')
                ? ' ' . $oShipping->getStreetNumber()
                : ''
            )
        );
        if ($this->config->assertMinimumVersion('5.0.0')
            && $oShipping->getAdditionalAddressLine1()
        ) {
            $oShippingAddress->setStreet2($oShipping->getAdditionalAddressLine1());
        }
        $oShippingAddress->setZipcode($oShipping->getZipCode());
        $oShippingAddress->setCity($oShipping->getCity());

        $countryId = $this->config->assertMinimumVersion('5.5.0')
            ? $oShipping->getCountry()->getId()
            : $oShipping->getCountryId();

        $oCountry = Shopware()->Models()->find('\Shopware\Models\Country\Country', $countryId);
        $oShippingAddress->setCountry($oCountry->getIso());

        if ($this->config->assertMinimumVersion('5.5.0')) {
            $stateId = $oShipping->getState()
                ? $oShipping->getState()->getId()
                : null;
        } else {
            $stateId = $oShipping->getStateId();
        }

        if ($stateId) {
            /** @var Shopware\Models\Country\State $state */
            $state = Shopware()->Models()->find('\Shopware\Models\Country\State', $stateId);
            $oShippingAddress->setState($oCountry->getIso() . '-' . $state->getShortCode());
        }

        $aAddresses[] = $oShippingAddress;

        $userData->setAddresses($aAddresses);

        return $userData;
    }

    /**
     * Takes a username and password along with the data for the new customer and tries to register a new customer
     * using the given information
     *
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_register_customer#API_Response
     *
     * @param string           $email
     * @param string           $pass
     * @param ShopgateCustomer $customer
     *
     * @throws ShopgateLibraryException
     */
    public function registerCustomer($email, $pass, ShopgateCustomer $customer)
    {
        // check for existing non guest accounts
        $sql      = "SELECT id FROM `s_user` WHERE email = ? and accountmode=0";
        $userData = Shopware()->Db()->fetchRow($sql, array($email));

        if (!empty($userData)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_USER_ALREADY_EXISTS);
        }

        $this->customerImport->createNewCustomer($email, $pass, $customer);
    }

    ############################################################################
    ## CREATE CATEGRIES CSV                                                   ##
    ############################################################################

    /**
     * @param \Shopware\Models\Category\Category $catgory
     */
    protected function setMaxCategoryPosition($catgory)
    {
        if (method_exists($catgory, 'getRight')) {
            $this->iMaxCategoryPosition = $catgory->getRight() + 1;
        } else {
            $sql                        = "SELECT MAX(`position`) as `maxposition` FROM `s_categories`";
            $this->iMaxCategoryPosition = (Shopware()->Db()->fetchOne($sql) + 1);
        }
    }

    protected function createCategoriesCsv()
    {
        $dql = "SELECT c FROM \Shopware\Models\Category\Category c WHERE c.id = :root_category";
        /* @var $rootCategory \Shopware\Models\Category\Category */
        $rootCategory = Shopware()->Models()->createQuery($dql)
            ->setParameter("root_category", $this->config->getRootCategory())
            ->getOneOrNullResult();
        $this->setMaxCategoryPosition($rootCategory);

        foreach ($rootCategory->getChildren() as $category) {
            /* @var $category \Shopware\Models\Category\Category */

            // Ignore Blogs from Shopware
            if ($category->getBlog()
                || $category->getExternal()
            ) {
                continue;
            }

            $this->buildCategoriesTree($category);
        }
    }

    protected function buildCategoriesTree(\Shopware\Models\Category\Category $category)
    {
        $aRow = $this->buildDefaultCategoryRow();
        $aRow = $this->executeLoaders($this->getCreateCategoriesCsvLoaders(), $aRow, $category);

        $this->addItem($aRow);

        foreach ($category->getChildren() as $child) {
            /* @var $child \Shopware\Models\Category\Category */

            if ($child->getBlog()
                || $child->getExternal()
            ) {
                continue;
            }

            $this->buildCategoriesTree($child);
        }
    }

    /**
     * Fill the Field category_number in the given array
     *
     * @param array                              $aItem
     * @param \Shopware\Models\Category\Category $aCategory
     *
     * @return array
     */
    protected function categoryExportCategoryNumber($aItem, \Shopware\Models\Category\Category $aCategory)
    {
        $aItem["category_number"] = $aCategory->getId();

        return $aItem;
    }

    /**
     * Fill the Field category_name in the given array
     *
     * @param array                              $aItem
     * @param \Shopware\Models\Category\Category $aCategory
     *
     * @return array
     */
    protected function categoryExportCategoryName($aItem, \Shopware\Models\Category\Category $aCategory)
    {
        $aItem["category_name"] = $aCategory->getName();

        return $aItem;
    }

    /**
     * Fill the Field parent_id in the given array
     *
     * @param array                              $aItem
     * @param \Shopware\Models\Category\Category $aCategory
     *
     * @return array
     */
    protected function categoryExportParentId($aItem, \Shopware\Models\Category\Category $aCategory)
    {
        $iParentId = "";
        if (($aCategory->getParentId() != $this->shop->getCategory()->getId())
            && ($aCategory->getParentId() != $aCategory->getId())
        ) {
            $iParentId = $aCategory->getParentId();
        }

        $aItem["parent_id"] = $iParentId;

        return $aItem;
    }

    /**
     * Fill the Field order_index in the given array
     *
     * @param array                              $aItem
     * @param \Shopware\Models\Category\Category $aCategory
     *
     * @return array
     */
    protected function categoryExportOrderIndex($aItem, \Shopware\Models\Category\Category $aCategory)
    {
        if (method_exists($aCategory, 'getLeft')) {
            $shopwareOrderIndex = $aCategory->getLeft();
        } else {
            $shopwareOrderIndex = $aCategory->getPosition();
        }
        $aItem["order_index"] = $this->iMaxCategoryPosition - $shopwareOrderIndex;

        return $aItem;
    }

    /**
     * Fill the Field is_active in the given array
     *
     * @param array                              $aItem
     * @param \Shopware\Models\Category\Category $aCategory
     *
     * @return array
     */
    protected function categoryExportIsActive($aItem, \Shopware\Models\Category\Category $aCategory)
    {
        $aItem["is_active"] = $aCategory->getActive();

        return $aItem;
    }

    /**
     * <strong>NOTE:</strong> SHOPWARE DOES NOT SUPPORT CATEGORY-IMAGES
     *
     * Fill the Field url_image in the given array
     *
     * @param array                              $aItem
     * @param \Shopware\Models\Category\Category $aCategory
     *
     * @return array
     */
    protected function categoryExportUrlImage($aItem, \Shopware\Models\Category\Category $aCategory)
    {
        if ($aCategory->getMedia()) {
            $baseUrl = "http://";
            $baseUrl .= Shopware()->Shop()->getHost();
            $baseUrl .= Shopware()->Shop()->getBasePath();
            $baseUrl .= "/";
            // It is possible to have inconsistent data that appears the getMedia() method to return an invalid media object, without really being existent
            // -> callin the getPath Method would throw an exception in that case
            try {
                $baseUrl .= $aCategory->getMedia()->getPath();
            } catch (Exception $e) {
                $baseUrl = '';
            }

            $aItem["url_image"] = $baseUrl;
        }

        return $aItem;
    }

    /**
     * Fill the Field url_deeplink in the given array
     *
     * @param array                              $aItem
     * @param \Shopware\Models\Category\Category $aCategory
     *
     * @return array
     */
    protected function categoryExportUrlDeeplink($aItem, \Shopware\Models\Category\Category $aCategory)
    {
        $sLink = $this->system->sCONFIG['sBASEFILE'] . "?sViewport=cat&sCategory={$aCategory->getId()}";
        $sLink = $this->system->sMODULES['sCore']->sRewriteLink($sLink);

        $aItem["url_deeplink"] = $sLink;

        return $aItem;
    }

    ############################################################################
    ## CREATE ITEMS CSV                                                       ##
    ############################################################################

    protected function getCreateItemsCsvLoaders()
    {
        $loaders = parent::getCreateItemsCsvLoaders();
        /** $this->itemExportAttributes */
        $loaders[] = "itemExportAttributes";

        return $loaders;
    }

    protected function buildDefaultItemRow()
    {
        $bExportNetto = false;

        static $row;

        if (empty($row)) {
            $row = parent::buildDefaultItemRow();

            if ($bExportNetto) {
                // remove old fileds
                unset($row["unit_amount"]);
                unset($row["old_unit_amount"]);
                unset($row["tax_percent"]);

                $newFields = array(
                    "tax_class"           => "",
                    /** $this->itemExportTaxClass */
                    "unit_amount_net"     => "0",
                    /** $this->itemExportUnitAmountNet */
                    "old_unit_amount_net" => "",
                    /** $this->itemExportOldUnitAmountNet */
                );

                $row = array_slice($row, 0, 3, true) +
                    $newFields +
                    array_slice($row, 3, count($row) - 3, true);
            }
        }

        return $row;
    }

    protected function getArticleExportIds($searchArticleIds = array(), $skipArticleIds = array())
    {
        if (!empty($skipArticleIds)) {
            $this->log(
                "ShopgatePluginShopware::createItemsCsv skipping article detail [articleDetailId-List={" . implode(
                    ', ',
                    $skipArticleIds
                ) . "}]",
                ShopgateLogger::LOGTYPE_DEBUG
            );
        }

        $dql = "
			SELECT d.id
			FROM \Shopware\Models\Article\Detail d
				JOIN d.article a
			WHERE a.mode = 0
				AND a.active = 1
				AND d.active = 1
			" .
            (!empty($searchArticleIds)
                ? "  AND a.id IN ('" . implode("','", $searchArticleIds) . "')
			"
                : "") .
            (!empty($skipArticleIds)
                ? "  AND a.id NOT IN ('" . implode("','", $skipArticleIds) . "')
			"
                : "") .
            "ORDER BY d.id";

        $q = Shopware()->Models()->createQuery($dql);

        if ($this->splittedExport) {
            $q->setFirstResult($this->exportOffset);
            $q->setMaxResults($this->exportLimit);
        }

        $ids = array();
        foreach ($q->getArrayResult() as $data) {
            $ids[] = $data["id"];
        }

        return $ids;
    }

    /**
     * (non-PHPdoc)
     *
     * @see ShopgatePlugin::createItemsCsv()
     */
    protected function createItemsCsv()
    {
        $memoryUsageBegin     = memory_get_usage();
        $memoryUsageRealBegin = memory_get_usage(true);

        // set export model to product until own csv model exists
        $this->exportModel = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product(
            $this->exportComponent
        );
        $this->exportModel->setConfig($this->config);

        $this->log(
            "ShopgatePluginShopware::createItemsCsv() memory usage at beginning: " . $this->getMemoryUsageString(),
            ShopgateLogger::LOGTYPE_DEBUG
        );

        if (!$this->splittedExport || $this->exportOffset == 0) {
            $dql = "
			SELECT COUNT(d.id)
				FROM \Shopware\Models\Article\Detail d
				JOIN d.article a
			WHERE a.mode = 0
				AND a.active = 1
				AND d.active = 1";

            $count = Shopware()->Models()->createQuery($dql)->getSingleScalarResult();
            $this->log("Active Products in Database: {$count}", ShopgateLogger::LOGTYPE_ACCESS);
        }

        $this->log(
            "ShopgatePluginShopware::createItemsCsv() loading article export ids",
            ShopgateLogger::LOGTYPE_DEBUG
        );
        $searchArticleIds = array();
        if (!empty($this->params['item_numbers']) && is_array($this->params['item_numbers'])) {
            $searchArticleIds = $this->params['item_numbers'];
        }

        $skipIds          = $this->config->getExcludeItemIds();
        $articleDetailIds = $this->getArticleExportIds($searchArticleIds, $skipIds);
        $this->log(
            "ShopgatePluginShopware::createItemsCsv() memory usage so far: " . $this->getMemoryUsageString(),
            ShopgateLogger::LOGTYPE_DEBUG
        );

        foreach ($articleDetailIds as $articleDetailId) {
            $this->log(
                "ShopgatePluginShopware::createItemsCsv memory usage BEFORE product export [articleDetailId={$articleDetailId}]: "
                . $this->getMemoryUsageString(),
                ShopgateLogger::LOGTYPE_DEBUG
            );

            // reset post data
            Shopware()->System()->_POST['group'] = array();

            /* @var $oDetail \Shopware\Models\Article\Detail */
            $oDetail = Shopware()->Models()->find('\Shopware\Models\Article\Detail', $articleDetailId);
            $this->exportModel->setDetail($oDetail);

            /* @var $article Shopware\Models\Article\Article */
            $article = $oDetail->getArticle();

            // Debug code for extended article data
            if (ShopgateLogger::getInstance()->isDebugEnabled()) {
                if (!empty($this->params['dump_article_objects']) && $this->params['dump_article_objects'] == 1) {
                    $dumpArticle = false;
                    if (!empty($this->params['dump_article_detail_idlist'])
                        && is_array(
                            $this->params['dump_article_detail_idlist']
                        )
                    ) {
                        // dump only selected article-details
                        if (in_array($articleDetailId, $this->params['dump_article_detail_idlist'])) {
                            $dumpArticle = true;
                        }
                    } else {
                        // dump all available article-details
                        $dumpArticle = true;
                    }
                    // output directly to the csv file
                    if ($dumpArticle) {
                        $this->log(
                            "ShopgatePluginShopware::createItemsCsv dumping article-detail object [articleDetailId={$articleDetailId} / articleid={$article->getId()}]",
                            ShopgateLogger::LOGTYPE_DEBUG
                        );
                        echo "[Article-Detail-ID={$articleDetailId} / Article-ID={$article->getId()}]: ";
                        $this->user_print_r($oDetail);
                    }
                }
            }

            $valid = false;
            foreach ($article->getCategories()->getIterator() as $category) {
                /* @var $category \Shopware\Models\Category\Category */
                if ($this->exportComponent->checkCategory($category->getId())) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                continue;
            }

            try {
                $isParent = $this->getIsParent($article, $oDetail);

                if ($isParent) {
                    $articleArray = Shopware()->Modules()->Articles()->sGetArticleById($article->getId());

                    if ($article->getDetails()->count() > 1) {
                        // parent item
                        $sgItem = $this->buildDefaultItemRow();
                        $sgItem = $this->executeLoaders(
                            $this->getCreateItemsCsvLoaders(),
                            $sgItem,
                            $article,
                            $oDetail,
                            $articleArray,
                            true
                        );
                        $this->addItem($sgItem);
                    }
                }

                if ($article->getDetails()->count() > 1) {
                    // child item -> load post params for the detail
                    $post = array();

                    /* @var $option \Shopware\Models\Article\Configurator\Option */
                    foreach ($oDetail->getConfiguratorOptions() as $option) {
                        $post[$option->getGroup()->getId()] = $option->getId();
                    }

                    if (!empty($post)) {
                        Shopware()->System()->_POST['group'] = $post;
                    }
                }

                // child or non variation item
                $articleArray = Shopware()->Modules()->Articles()->sGetArticleById($article->getId());
                $sgItem       = $this->buildDefaultItemRow();
                $sgItem       = $this->executeLoaders(
                    $this->getCreateItemsCsvLoaders(),
                    $sgItem,
                    $article,
                    $oDetail,
                    $articleArray,
                    false
                );
                $this->addItem($sgItem);
            } catch (Exception $e) {
                $msg = "Product with id #{$articleDetailId} cannot export!\n";
                $msg .= "Exception: " . get_class($e) . "\n";
                $msg .= "Message: " . $e->getMessage() . "\n";
                $msg .= "Trace:\n" . $e->getTraceAsString();
                $this->log($msg);
            }

            // free memory
            if (isset($oDetail)) {
                unset($oDetail);
            }
            if (isset($article)) {
                unset($article);
            }

            $this->log(
                "ShopgatePluginShopware::createItemsCsv() memory usage AFTER product export [articleId={$articleDetailId}]: "
                . $this->getMemoryUsageString(),
                ShopgateLogger::LOGTYPE_DEBUG
            );
        }

        // output memory footprint to debug log
        $memoryUsageDiff     = memory_get_usage() - $memoryUsageBegin;
        $memoryUsageRealDiff = memory_get_usage(true) - $memoryUsageRealBegin;
        $memUsageDiffString  = $this->getFormatedMemoryDiffString($memoryUsageDiff, $memoryUsageRealDiff);
        $this->log(
            "ShopgatePluginShopware::createItemsCsv() memory usage DIFFERENCE after creating the CSV file: "
            . $memUsageDiffString,
            ShopgateLogger::LOGTYPE_DEBUG
        );
    }

    /**
     * Fill the Field item_number in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     * @param bool                             $isParent
     *
     * @return array
     */
    protected function itemExportItemNumber(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article,
        $isParent = false
    ) {
        if ($aArticle->getConfiguratorSet() && $isParent) {
            $aItem["item_number"] = $aArticle->getId();
        } else {
            $aItem["item_number"] = $aArticle->getId() . "-" . $details->getNumber();
        }

        return $aItem;
    }

    /**
     * Fill the Field item_number_public in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportItemNumberPublic(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $aItem["item_number_public"] = $details->getNumber();

        return $aItem;
    }

    /**
     * Fill the Field item_name in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return array
     */
    protected function itemExportItemName(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $aItem["item_name"] = $article['articleName'];

        return $aItem;
    }

    /**
     * Fill the Field unit_amount in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return array
     */
    protected function itemExportUnitAmount(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $price = $this->formatArticlePrice($article['price']);

        if (!empty($article['purchasesteps'])) {
            $price *= $article['purchasesteps'];
        }

        $aItem["unit_amount"] = $this->formatArticlePrice($price);

        return $aItem;
    }

    /**
     * Fill the Field unit_amount in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return array
     */
    protected function itemExportUnitAmountNet(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $price = $this->formatArticlePrice($article['price']);
        $tax   = $article['tax'];

        if (!empty($article['purchasesteps'])) {
            $price *= $article['purchasesteps'];
        }

        $aItem["unit_amount_net"] = $this->formatPriceNumber($this->formatArticlePrice($price) / (1 + ($tax / 100)), 2);

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return mixed
     */
    protected function itemExportOldUnitAmount(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $price = $this->formatArticlePrice($article['pseudoprice']);

        if (!empty($article['purchasesteps'])) {
            $price *= $article['purchasesteps'];
        }

        $aItem["old_unit_amount"] = $this->formatArticlePrice($price);

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return mixed
     */
    protected function itemExportOldUnitAmountNet(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $price = $article['pseudoprice'];
        $tax   = $article['tax'];

        if (!empty($article['purchasesteps'])) {
            $price *= $article['purchasesteps'];
        }

        $aItem["old_unit_amount_net"] = $this->formatPriceNumber(
            $this->formatArticlePrice($price) / (1 + ($tax / 100)),
            2
        );

        return $aItem;
    }

    /**
     * Fill the Field currency in the given array
     *
     * @param array $aItem
     *
     * @return array
     */
    protected function itemExportCurrency($aItem)
    {
        $aItem["currency"] = $this->system->sCurrency['currency'];

        return $aItem;
    }

    /**
     * Fill the Field tax_percent in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return array
     */
    protected function itemExportTaxPercent($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $aItem["tax_percent"] = $aArticle->getTax()->getTax();

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return array
     */
    protected function itemExportTaxClass($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $aItem["tax_class"] = "tax_{$aArticle->getTax()->getTax()}_{$aArticle->getTax()->getId()}";

        return $aItem;
    }

    /**
     * Fill the Field description in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArtcile
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return array
     */
    protected function itemExportDescription(
        $aItem,
        \Shopware\Models\Article\Article $aArtcile,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $aItem["description"] = $this->exportModel->prepareDescription($article);

        return $aItem;
    }

    /**
     * Fill the Field urls_images in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     * @param bool                             $isParent
     *
     * @return array
     */
    protected function itemExportUrlsImages(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article,
        $isParent = false
    ) {
        $aImages              = $this->exportModel->getImageUrls($aArticle, $details, $isParent);
        $aItem["urls_images"] = implode("||", $aImages);

        return $aItem;
    }

    /**
     * Fill the Field categories in the given array
     *
     * @todo Filter for other SubShops
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportCategories(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $paths = array();
        foreach ($aArticle->getCategories()->getIterator() as $cat) {
            /* @var $cat \Shopware\Models\Category\Category */

            if ($cat->getBlog()
                || !$this->exportComponent->checkCategory($cat->getId())
            ) {
                continue;
            }

            $path = array();

            while ($cat) {
                if ($cat->getBlog()) {
                    break;
                }

                $path[] = $cat->getName();
                $cat    = $cat->getParent();
            }

            array_pop($path);
            array_pop($path);

            $paths[] = implode("=>", array_reverse($path));
        }

        $aItem["categories"] = implode('||', $paths);

        return $aItem;
    }

    /**
     * Fill the Field category_numbers in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportCategoryNumbers($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $categories = $this->exportModel->getCategories($aArticle);
        $catIds     = array();

        foreach ($categories as $category) {
            $sortOrder = $category['sort_order']
                ? '=>' . $category['sort_order']
                : '';
            $catIds[]  = $category['id'] . $sortOrder;
        }

        $aItem["category_numbers"] = implode('||', $catIds);

        return $aItem;
    }

    /**
     * Fill the Field is_available in the given array
     *
     * @param array $aItem
     *
     * @return array
     */
    protected function itemExportIsAvailable($aItem)
    {
        $aItem["is_available"] = 1;

        return $aItem;
    }

    /**
     * Fill the Field available_text in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportAvailableText(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $sAvailableText          = $this->exportModel->getAvailableText($details);
        $aItem["available_text"] = $sAvailableText;

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return array
     */
    protected function itemExportBasicPrice(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $price                = $this->exportModel->getFormattedPrice($article['price']);
        $aItem["basic_price"] = $this->exportModel->prepareBasePrice($price, $details);

        return $aItem;
    }

    /**
     * Fill the Field url_deeplink in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return array
     */
    protected function itemExportUrlDeeplink(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $url                   = $article['linkDetailsRewrited'];
        $aItem["url_deeplink"] = $url;

        return $aItem;
    }

    /**
     * Fill the Field properties in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     *
     * @return array
     */
    protected function itemExportProperties(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article
    ) {
        $tmpProperties = $this->exportModel->prepareProperties($article, $details);
        $properties    = array();

        foreach ($tmpProperties as $name => $value) {
            $value        = implode(', ', $value);
            $properties[] = "{$name}=>{$value}";
        }

        $aItem["properties"] = implode("||", $properties);

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return array
     */
    protected function itemExportIsHighlight($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $aItem["is_highlight"] = $aArticle->getHighlight();

        return $aItem;
    }

    /**
     * Fill the Field ean in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportEan(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $aItem["ean"] = $details->getEan();

        return $aItem;
    }

    /**
     * * Fill the Field use_stock in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return mixed
     */
    protected function itemExportUseStock($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $aItem["use_stock"] = $aArticle->getLastStock()
            ? 1
            : 0;

        return $aItem;
    }

    /**
     * Fill the Field active_status in the given array
     *
     * @param                                  $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return mixed
     */
    protected function itemExportActiveStatus($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $aItem["active_status"] = $aArticle->getLastStock()
            ? "active"
            : "stock";

        if (!$aArticle->getActive()) {
            $aItem["active_status"] = 'inactive';
        }

        return $aItem;
    }

    /**
     * Fill the Field stock_quantity in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportStockQuantity(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $stock = $details->getInStock();
        if ($details->getPurchaseSteps()) {
            $stock = floor($stock / $details->getPurchaseSteps());
        }

        $aItem["stock_quantity"] = $stock;

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportMinimumOrderQuantity(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $minQuanity = $details->getMinPurchase();

        if ($details->getPurchaseSteps()) {
            $minQuanity = ceil($minQuanity / $details->getPurchaseSteps());
        }

        $aItem["minimum_order_quantity"] = $minQuanity;

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportMaximumOrderQuantity(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $maxQuantity = $details->getMaxPurchase();

        if ($details->getPurchaseSteps()) {
            $maxQuantity = floor($maxQuantity / $details->getPurchaseSteps());
        }

        $aItem["maximum_order_quantity"] = $maxQuantity;

        return $aItem;
    }

    /**
     * Fill the Field manufacturer in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return array
     */
    protected function itemExportManufacturer($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        if ($aArticle->getSupplier()) {
            $aItem["manufacturer"] = $aArticle->getSupplier()->getName();
        }

        return $aItem;
    }

    /**
     * Fill the Field manufacturer_item_number in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportManufacturerItemNumber(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $aItem["manufacturer_item_number"] = $details->getSupplierNumber();

        return $aItem;
    }

    /**
     * Fill the Field last_update in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return array
     */
    protected function itemExportLastUpdate($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $aItem["last_update"] = $aArticle->getChanged()->format("c");

        return $aItem;
    }

    /**
     * Fill the Field tags in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return array
     */
    protected function itemExportTags($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $aItem["tags"] = $aArticle->getKeywords();

        return $aItem;
    }

    /**
     * Fill the Field is_free_shipping in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportIsFreeShipping(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $aItem["is_free_shipping"] = $details->getShippingFree();

        return $aItem;
    }

    /**
     * Fill the Field weight in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportWeight(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $aItem["weight"] = $this->exportModel->prepareWeight($details);

        return $aItem;
    }

    /**
     * Fill the Field related_shop_item_numbers in the given array
     *
     * @param                                  $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportRelatedShopItemNumbers(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $relatedArticles                    = $this->exportModel->getRelatedItems($aArticle, $details);
        $aItem["related_shop_item_numbers"] = implode('||', $relatedArticles);

        return $aItem;
    }

    /**
     * Fill the Field related_shop_items in the given array
     *
     * @param                                  $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     *
     * @return string
     */
    protected function itemExportRelatedShopItems($aItem, \Shopware\Models\Article\Article $aArticle)
    {
        $relations = array();

        $similarArticles = $this->exportModel->getSimilarItems($aArticle);
        foreach ($similarArticles as $similarArticleId) {
            $relations[] = array(
                'type'        => Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL,
                'item_number' => $similarArticleId,
                'restricted'  => false,
            );
        }

        $relatedArticles = $this->exportModel->getRelatedItems($aArticle);
        foreach ($relatedArticles as $relatedArticleId) {
            $relations[] = array(
                'type'        => Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL,
                'item_number' => $relatedArticleId,
                'restricted'  => false,
            );
        }

        if (!empty($relations)) {
            $aItem['related_shop_items'] = $this->jsonEncode($relations);
        }

        return $aItem;
    }

    /**
     * Fill the Field internal_order_info in the given array
     *
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportInternalOrderInfo(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        $infos                        = $this->exportModel->prepareInternalOrderInfo($aArticle, $details);
        $aItem["internal_order_info"] = $this->jsonEncode($infos);

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return array
     */
    protected function itemExportBlockPricing(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        if ($details->getPrices()->count() > 1) {
            $block_prices = array();
            foreach ($details->getPrices() as $price) {
                /** @var $price \Shopware\Models\Article\Price */
                $amount         = $price->getPrice() * (1 + ($aArticle->getTax()->getTax() / 100));
                $amount         = $this->formatPriceNumber($amount);
                $block_prices[] = "{$price->getFrom()}=>{$amount}";
            }

            $aItem["block_pricing"] = implode("||", $block_prices);
        }

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     * @param bool                             $isParent
     *
     * @return array
     */
    protected function itemExportHasChildren(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article,
        $isParent = false
    ) {
        $aItem["has_children"] = "0";

        if ($aArticle->getConfiguratorSet() && $isParent) {
            $aItem["has_children"] = "1";
        }

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     * @param array                            $article
     * @param bool                             $isParent
     *
     * @return array
     */
    protected function itemExportParentItemNumber(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details,
        $article,
        $isParent = false
    ) {
        if (!$isParent && $aArticle->getDetails()->count() > 1) {
            $aItem["parent_item_number"] = $aArticle->getId();
        }

        return $aItem;
    }

    /**
     * @param array                            $aItem
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $oDetail
     * @param array                            $article
     * @param bool                             $isParent
     *
     * @return array
     */
    protected function itemExportAttributes(
        $aItem,
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $oDetail,
        $article,
        $isParent = false
    ) {
        $i = 1;
        if ($aArticle->getConfiguratorSet()) {
            if ($isParent) {
                $i = 1;
                /* @var $oGroup Shopware\Models\Article\Configurator\Group */
                foreach ($aArticle->getConfiguratorSet()->getGroups() as $oGroup) {
                    // look up translation
                    $translatedAttributeName =
                        $this->translation->read($this->locale->getId(), 'configuratorgroup', $oGroup->getId());

                    // if no translation was found look up fallback
                    if (empty($translatedAttributeName) && ($fallback = Shopware()->Shop()->getFallback())) {
                        $translatedAttributeName =
                            $this->translation->read($fallback->getId(), 'configuratorgroup', $oGroup->getId());
                    }

                    $aItem["attribute_{$i}"] =
                        (!empty($translatedAttributeName['name']))
                            ? $translatedAttributeName['name']
                            : $oGroup->getName();
                    $i++;
                }
            } else {
                $map = array();
                foreach ($aArticle->getConfiguratorSet()->getGroups() as $oGroup) {
                    $map[$oGroup->getId()] = $i;
                    $i++;
                }

                /* @var $oOption \Shopware\Models\Article\Configurator\Option */
                foreach ($oDetail->getConfiguratorOptions() as $oOption) {
                    $oGroup = $oOption->getGroup();
                    $i      = $map[$oGroup->getId()];

                    // look up translation
                    $translatedAttributeName =
                        $this->translation->read($this->locale->getId(), 'configuratoroption', $oOption->getId());

                    // if no translation was found look up fallback
                    if (empty($translatedAttributeName) && ($fallback = Shopware()->Shop()->getFallback())) {
                        $translatedAttributeName =
                            $this->translation->read($fallback->getId(), 'configuratoroption', $oOption->getId());
                    }

                    $aItem["attribute_{$i}"] =
                        (!empty($translatedAttributeName['name']))
                            ? $translatedAttributeName['name']
                            : $oOption->getName();
                }
            }
        }

        return $aItem;
    }


    ############################################################################
    ## EXPORT HELPER                                                          ##
    ############################################################################

    /**
     * @param \Shopware\Components\Model\ModelEntity $article
     * @param null                                   $field
     *
     * @return mixed
     */
    protected function getArticleTranslation(Shopware\Components\Model\ModelEntity $article, $field = null)
    {
        $selectField = $field
            ? "*"
            : $field;

        $qry = "
		SELECT {$field}
		FROM s_articles_translations
		WHERE articleID = {$article->getId()}
		AND languageID = {$this->langId}";

        if ($field) {
            return Shopware()->Db()->fetchOne($qry);
        } else {
            return Shopware()->Db()->fetchRow($qry);
        }
    }

    /**
     * @param \Shopware\Models\Article\Article $aArticle
     * @param \Shopware\Models\Article\Detail  $details
     *
     * @return \Shopware\Models\Article\Price
     * @throws Exception
     */
    protected function getArticlePrice(
        \Shopware\Models\Article\Article $aArticle,
        \Shopware\Models\Article\Detail $details
    ) {
        /* @var $price \Shopware\Models\Article\Price */
        $dql = "SELECT p FROM \Shopware\Models\Article\Price p
			WHERE p.articleId = :aID
			AND p.articleDetailsId = :adID
			AND p.customerGroupKey = :groupKey
			ORDER BY p.from ASC";

        $price = Shopware()->Models()->createQuery($dql)
            ->setMaxResults(1)
            ->setParameter("aID", $aArticle->getId())
            ->setParameter("adID", $details->getId())
            ->setParameter("groupKey", $this->defaultCustomerGroupKey)
            ->getOneOrNullResult();
        if (empty($price)) {
            $price = Shopware()->Models()->createQuery($dql)
                ->setMaxResults(1)
                ->setParameter("aID", $aArticle->getId())
                ->setParameter("adID", $details->getId())
                ->setParameter("groupKey", "EK")
                ->getOneOrNullResult();
        }

        if (!$price) {
            throw new \Exception("Article detail#{$details->getId()} has no customer price");
        }

        return $price;
    }

    /**
     * Takes a string price value with a comma or dot and creates a float value out of it before converting to a price
     * format
     *
     * @param string $price
     *
     * @return float
     */
    protected function formatArticlePrice($price)
    {
        return $this->formatPriceNumber(str_replace(",", ".", $price), 2);
    }

    /**
     * return formated memory diff, used by export.
     *
     * @param $memoryUsageDiff
     * @param $memoryUsageRealDiff
     *
     * @return string
     */
    protected function getFormatedMemoryDiffString($memoryUsageDiff, $memoryUsageRealDiff)
    {
        switch (strtoupper(trim(ShopgateLogger::getInstance()->getMemoryAnalyserLoggingSizeUnit()))) {
            case 'GB':
                $memUsageDiffString =
                    ($memoryUsageDiff / (1024 * 1024 * 1024)) . " GB (real usage " . ($memoryUsageRealDiff / (1024
                            * 1024 * 1024)) . " GB)";
                break;
            case 'MB':
                $memUsageDiffString =
                    ($memoryUsageDiff / (1024 * 1024)) . " MB (real usage " . ($memoryUsageRealDiff / (1024
                            * 1024)) . " MB)";
                break;
            case 'KB':
                $memUsageDiffString =
                    ($memoryUsageDiff / 1024) . " KB (real usage " . ($memoryUsageRealDiff / 1024) . " KB)";
                break;
            default:
                $memUsageDiffString = $memoryUsageDiff . " Bytes (real usage " . $memoryUsageRealDiff . " Bytes)";
                break;
        }

        return $memUsageDiffString;
    }

    /**
     * @param \Shopware\Models\Article\Article $article
     * @param \Shopware\Models\Article\Detail  $detail
     *
     * @return bool
     */
    protected function getIsParent($article, $detail)
    {
        // Normally every product has at least one "article_details" entry and has set a "main_detail_id" as well
        if ($article->getMainDetail()) {
            // Check if the actual detail is the main detail
            // If so, it is a parent only if there is more than one detail
            // TODO: This can be probably an issue in cases where the product has only one possible variation
            $isParent = ($article->getMainDetail()->getId() == $detail->getId() && $article->getDetails()->count() > 1);
        } else {
            // Assume as parent in case there are no details (which is most unlikely to happen)
            $isParent = $article->getDetails()->count() < 1;
        }

        return $isParent;
    }

    ############################################################################
    ## REVIEWS-CSV                                                            ##
    ############################################################################

    /**
     * prepares the query for the review export
     *
     * @param int   $offset
     * @param int   $limit
     * @param array $ids
     *
     * @return mixed
     */
    protected function getReviewExportQuery($offset = null, $limit = null, $ids = array())
    {
        $builder = Shopware()->Models()->getRepository('Shopware\Models\Article\Vote')
            ->createQueryBuilder('vote');

        if ($offset !== null && $limit !== null) {
            $builder->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $builder->where('vote.active = 1');

        if (!empty($ids) && is_array($ids)) {
            $builder->andWhere($builder->expr()->in('vote.articleId', ':articleIds'))
                ->setParameter('articleIds', $ids);
        } else {
            $skipIds = $this->config->getExcludeItemIds();
            if (!empty($skipIds)) {
                $builder->andWhere($builder->expr()->notIn('vote.articleId', ':skipIds'))
                    ->setParameter('skipIds', $skipIds);
            }
        }

        return $builder->getQuery();
    }

    /**
     * @see lib/ShopgatePluginCore::startCreateReviewsCsv()
     */
    protected function createReviewsCsv()
    {

        // if there is a request param called "article_ids" only proces reviews for those items (for debugging purposes)
        $itemNumbers = (!empty($this->params['article_ids']))
            ? implode(",", $this->params['article_ids'])
            : array();

        // Quick fix until next release of ShopgateLibrary
        if (isset($this->params["limit"]) && isset($this->params["offset"])) {
            $offset = $this->params["offset"];
            $limit  = $this->params["limit"];
        } else {
            $offset = null;
            $limit  = null;
        }

        $qry = $this->getReviewExportQuery($offset, $limit, $itemNumbers);

        foreach ($qry->getResult() as $review) {
            try {
                /* @var $review \Shopware\Models\Article\Vote */
                if (!$review->getArticle()
                    || !$review->getArticle()->getDetails()
                    || !$review->getArticle()->getActive()
                    || $review->getArticle()->getMode() != 0
                ) {
                    continue;
                }

                $row = $this->buildDefaultReviewsRow();
                $row = $this->executeLoaders($this->getCreateReviewsCsvLoaders(), $row, $review);

                $this->addItem($row);
            } catch (Exception $e) {
                $this->log($e->getMessage());
            }
        }
    }

    /**
     * the item number the vote belongs to
     *
     * @param array                         $row
     * @param \Shopware\Models\Article\Vote $review
     *
     * @return array
     */
    protected function reviewExportItemNumber(array $row, \Shopware\Models\Article\Vote $review)
    {
        $row["item_number"] = $review->getArticle()->getDetails()->first()->getNumber();

        return $row;
    }

    /**
     * the unique identifier of the vote to update on shopgate
     *
     * @param array                         $row
     * @param \Shopware\Models\Article\Vote $review
     *
     * @return array
     */
    protected function reviewExportUpdateReviewId(array $row, \Shopware\Models\Article\Vote $review)
    {
        $row["update_review_id"] = $review->getId();

        return $row;
    }

    /**
     * Calculate and export the score of the vote
     *
     * in Shopware points can be between 0.5 and 5 in 0.5 steps
     *
     * in shopgate the score is between 1 and 10
     *
     * @param array                         $row
     * @param \Shopware\Models\Article\Vote $review
     *
     * @return array
     */
    protected function reviewExportScore(array $row, \Shopware\Models\Article\Vote $review)
    {
        $row["score"] = $review->getPoints() * 2;

        return $row;
    }

    /**
     * The name of the author of the vote
     *
     * @param array                         $row
     * @param \Shopware\Models\Article\Vote $review
     *
     * @return array
     */
    protected function reviewExportName(array $row, \Shopware\Models\Article\Vote $review)
    {
        $row["name"] = $review->getName();

        return $row;
    }

    /**
     * the title
     *
     * @param array                         $row
     * @param \Shopware\Models\Article\Vote $review
     *
     * @return array
     */
    protected function reviewExportTitle(array $row, \Shopware\Models\Article\Vote $review)
    {
        $row["title"] = $review->getHeadline();

        return $row;
    }

    /**
     * export the text/comment of the vote
     *
     * @param array                         $row
     * @param \Shopware\Models\Article\Vote $review
     *
     * @return array
     */
    protected function reviewExportText(array $row, \Shopware\Models\Article\Vote $review)
    {
        $row["text"] = $review->getComment();

        return $row;
    }

    /**
     * Export the created date of the vote in the specified format
     *
     * @param array                         $row
     * @param \Shopware\Models\Article\Vote $review
     *
     * @return array
     */
    protected function reviewExportDate(array $row, \Shopware\Models\Article\Vote $review)
    {
        $row["date"] = $review->getDatum()->format("c");

        return $row;
    }


    ############################################################################
    ## MEDIA-CSV                                                              ##
    ############################################################################

    /**
     * (non-PHPdoc)
     *
     * @see ShopgatePlugin::createMediaCsv()
     */
    protected function createMediaCsv()
    {
    }

    ############################################################################
    ## ORDER                                                                  ##
    ############################################################################

    protected $customerId;

    protected $orderItemMap;

    protected $unfinishedOrderData;

    /**
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return array
     * @throws Exception
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $oShopgateOrder)
    {
        $this->customerId   = null;
        $iOrderNumber       = null;
        $this->orderItemMap = array();

        if ($this->_checkOrderExist($oShopgateOrder)) {
            $transactionId = $this->getTransactionId($oShopgateOrder);

            $sql                       = "
				SELECT ordernumber, userID
				FROM s_order
				WHERE transactionID LIKE '{$transactionId}%' AND remote_addr NOT LIKE 'shopgate.com.'
			";
            $this->unfinishedOrderData = Shopware()->Db()->fetchRow($sql);
            if (!empty($this->unfinishedOrderData)) {
                $iOrderNumber     = $this->unfinishedOrderData['ordernumber'];
                $this->customerId = $this->unfinishedOrderData['userID'];
            } else {
                throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER);
            }
        }

        if (empty($iOrderNumber) && empty($this->customerId)) {
            $this->log(
                "Start to add order into system #{$oShopgateOrder->getOrderNumber()}",
                ShopgateLogger::LOGTYPE_DEBUG
            );

            $this->log("Call add order loaders...", ShopgateLogger::LOGTYPE_DEBUG);

            $oShopwareOrder          = Shopware()->Modules()->Order();
            $oShopwareOrder->sSYSTEM = $this->system;

            /* @var $oShopwareOrder sOrder */
            $oShopwareOrder = $this->executeLoaders($this->getAddOrderLoaders(), $oShopwareOrder, $oShopgateOrder);

            $this->log(
                "Saving order to database Shopgate-Order-Number #{$oShopgateOrder->getOrderNumber()}",
                ShopgateLogger::LOGTYPE_DEBUG
            );
            $this->logOrder($oShopwareOrder);
            $partnerIdCode         = null;
            $trackingGetParameters = $oShopgateOrder->getTrackingGetParameters();
            foreach ($trackingGetParameters as $trackingGetParameter) {
                if ($trackingGetParameter['key'] == "sPartner") {
                    $partnerIdCode = $trackingGetParameter['value'];
                }
            }
            if (!is_null($partnerIdCode)) {
                Shopware()->Session()->sPartner = $partnerIdCode;
            }
            $iOrderNumber = $oShopwareOrder->sSaveOrder();

            if ($iOrderNumber === false) {
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                    "transactionID '{$oShopwareOrder->bookingId}' already exists!", true
                );
            }

            // set up the order item map for later use
            foreach ($oShopwareOrder->sBasketData['content'] as $key => $tmpArticle) {
                if (array_key_exists('order_item_id', $tmpArticle)) {
                    $this->orderItemMap[$tmpArticle['orderDetailId']] = $tmpArticle['order_item_id'];
                }
            }
        } else {
            $this->log("Incomplete order found. Order number is #{$iOrderNumber}", ShopgateLogger::LOGTYPE_DEBUG);
            $this->log("Trying to complete the order import", ShopgateLogger::LOGTYPE_DEBUG);
        }
        $this->log(
            "Shopware customer id is " . (isset($this->customerId)
                ? "#{$this->customerId}"
                : "<NULL>"),
            ShopgateLogger::LOGTYPE_DEBUG
        );
        $this->log("Shopware order number is #{$iOrderNumber}", ShopgateLogger::LOGTYPE_DEBUG);

        Shopware()->Models()->clear();

        $this->log("Reloading full order from database", ShopgateLogger::LOGTYPE_DEBUG);
        /** @var Order $oShopwareOrder */
        $oShopwareOrder = Shopware()->Models()
            ->getRepository("\Shopware\Models\Order\Order")
            ->findOneBy(array("number" => $iOrderNumber));

        if (!empty($this->nonInsertableOrderItems)) {
            $comment = "### Achtung ###:\n";
            $comment .= "Folgende Produkte konnten nicht problemfrei importiert werden und sollten ggf. überprüft werden:\n";
            $comment .= "(Dies kann z.B. vorkommen, wenn das Produkt zum Zeitpunkt des Imports nicht (mehr) bestellbar ist.)\n";
            foreach ($this->nonInsertableOrderItems as $item) {
                $amount  = $item->getUnitAmountWithTax() * $item->getQuantity();
                $comment .= "{$item->getQuantity()}x {$item->getItemNumber()} - {$item->getName(
                )} - {$amount} {$item->getCurrency()} \n";
            }
            $comment .= "\n";
            $this->updateInternalOrderComment($comment, $oShopwareOrder->getId());
        }

        $this->log("Setting remote address", ShopgateLogger::LOGTYPE_DEBUG);
        $sql = "UPDATE `s_order` SET remote_addr='shopgate.com.' WHERE id=" . $oShopwareOrder->getId();
        Shopware()->Db()->query($sql);

        $this->log("Calling \"after add order\" loaders...", ShopgateLogger::LOGTYPE_DEBUG);
        $oShopwareOrder = $this->executeLoaders($this->getAfterAddOrderLoaders(), $oShopwareOrder, $oShopgateOrder);

        $this->log("Flushing model data", ShopgateLogger::LOGTYPE_DEBUG);
        Shopware()->Models()->flush();
        $this->log("Finished add order!", ShopgateLogger::LOGTYPE_DEBUG);

        $data = array(
            "external_order_id"     => $oShopwareOrder->getId(),
            "external_order_number" => $iOrderNumber,
        );

        return $data;
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return string
     */
    private function getTransactionId(ShopgateOrder $shopgateOrder)
    {
        if ($shopgateOrder->getPaymentTransactionNumber()) {
            if ($this->isPaypalOrder($shopgateOrder)) {
                return $shopgateOrder->getPaymentTransactionNumber();
            }
            if ($this->isPaypalPlusOrder($shopgateOrder)) {
                return $shopgateOrder->getPaymentTransactionNumber();
            }
            if ($this->isPaypalUnifiedOrder($shopgateOrder)) {
                return $shopgateOrder->getPaymentTransactionNumber();
            }
            if ($this->isBillsafeOrder($shopgateOrder)) {
                return $shopgateOrder->getPaymentTransactionNumber();
            }
            if ($this->isPaymorrowOrder($shopgateOrder)) {
                return $shopgateOrder->getPaymentTransactionNumber();
            }
            $paymentInfos = $shopgateOrder->getPaymentInfos();
            if ($this->isAmazonPaymentsOrder($shopgateOrder)) {
                if (!empty($paymentInfos['mws_order_id'])) {
                    return $paymentInfos['mws_order_id'];
                }
            }
            if ($this->isSofortOrder($shopgateOrder)) {
                if (!empty($paymentInfos['transaction_id'])) {
                    return $paymentInfos['transaction_id'];
                }
            }
            if ($this->isPayolutionOrder($shopgateOrder)) {
                if (!empty($paymentInfos['transaction_id'])) {
                    return $paymentInfos['transaction_id'];
                }
            }
        }

        return $shopgateOrder->getOrderNumber();
    }

    /**
     * @param sOrder $shopwareOrder
     */
    private function logOrder(sOrder $shopwareOrder)
    {
        $this->log("-> Member-data in order object: {", ShopgateLogger::LOGTYPE_DEBUG);
        foreach (get_object_vars($shopwareOrder) as $key => $value) {
            if (!is_object($value)) {
                $this->log(
                    "\t{$key} = " . str_replace(
                        array("\r", "\n"),
                        array("", "\n\t"),
                        (is_null($value)
                            ? 'NULL'
                            : (empty($value)
                                ? 'EMPTY'
                                : print_r($value, true)))
                    ),
                    ShopgateLogger::LOGTYPE_DEBUG
                );
            } else {
                $this->log(
                    "\t{$key} = [object of type \"" . get_class($value) . "\"] ***see members below***",
                    ShopgateLogger::LOGTYPE_DEBUG
                );
                foreach (get_object_vars($value) as $key2 => $value2) {
                    if (!is_object($value2)) {
                        $this->log(
                            "\t\t{$key2} = " . str_replace(
                                array("\r", "\n"),
                                array("", "\n\t\t"),
                                (is_null($value2)
                                    ? 'NULL'
                                    : (empty($value2)
                                        ? 'EMPTY'
                                        : print_r($value2, true)))
                            ),
                            ShopgateLogger::LOGTYPE_DEBUG
                        );
                    } else {
                        $this->log(
                            "\t\t{$key2} = [object of type \"" . get_class($value2) . "\"]",
                            ShopgateLogger::LOGTYPE_DEBUG
                        );
                        $this->log(
                            "\t\t\t***no members of class \"" . get_class($value2) . "\" exposed here***",
                            ShopgateLogger::LOGTYPE_DEBUG
                        );
                    }
                }
            }
        }
        $this->log("-> }", ShopgateLogger::LOGTYPE_DEBUG);
    }

    public function updateOrder(ShopgateOrder $oShopgateOrder)
    {
        Shopware()->Models()->beginTransaction();

        $iOrderId = $this->_checkOrderExist($oShopgateOrder);
        if (!$iOrderId) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_ORDER_NOT_FOUND);
        }

        /** @var \Shopware\Models\Order\Order $oShopwareOrder */
        $oShopwareOrder = Shopware()->Models()->find("Shopware\Models\Order\Order", $iOrderId);

        if ($oShopgateOrder->getUpdatePayment()) {
            $oShopwareOrder = $this->setOrderPayment($oShopwareOrder, $oShopgateOrder);
            $oShopwareOrder = $this->setOrderClear($oShopwareOrder, $oShopgateOrder);
        }

        if ($oShopgateOrder->getUpdateShipping()) {
            $oShopwareOrder = $this->setOrderStatus($oShopwareOrder, $oShopgateOrder);
        }

        Shopware()->Models()->flush($oShopwareOrder);
        Shopware()->Models()->commit();

        $data = array(
            "external_order_id"     => $oShopwareOrder->getId(),
            "external_order_number" => $oShopwareOrder->getNumber(),
        );

        return $data;
    }

    /**
     * @param sOrder        $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return sOrder
     */
    protected function insertOrderPaymentCosts(sOrder $oOrder, ShopgateOrder $oShopgateOrder)
    {
        $orderNumberPaymentFeeConfig      = Shopware()->Config()->get('sPAYMENTSURCHARGEABSOLUTENUMBER');
        $orderNumberPaymentFeeLabelConfig = Shopware()->Config()->get('sPAYMENTSURCHARGEABSOLUTE');
        $orderNumberPaymentFeeLabel       = !empty($orderNumberPaymentFeeLabelConfig) ? $orderNumberPaymentFeeLabelConfig : "Zuschlag für Zahlungsart";
        $orderNumberPaymentFee            = !empty($orderNumberPaymentFeeConfig) ? $orderNumberPaymentFeeConfig : "sw-payment-absolute";
        if (floatval($oShopgateOrder->getAmountShopPayment()) > 0) {
            $aItem                 = array();
            $aItem['id']           = -1;
            $aItem['articleID']    = 0;
            $aItem['ordernumber']  = $orderNumberPaymentFee;
            $aItem['priceNumeric'] = $oShopgateOrder->getAmountShopPayment();
            $aItem['price']        = $oShopgateOrder->getAmountShopPayment();
            $aItem['quantity']     = 1;
            $aItem['amount']       = $aItem['price'] * $aItem['quantity'];
            $aItem['articlename']  = $orderNumberPaymentFeeLabel;
            $aItem['modus']        = "4";
            $aItem['taxID']        = 0;
            $aItem['tax_rate']     = "19";

            $oOrder->sBasketData["content"][] = $aItem;
        } elseif (floatval($oShopgateOrder->getAmountShopPayment()) < 0) {
            $aItem                 = array();
            $aItem['id']           = -1;
            $aItem['articleID']    = 0;
            $aItem['ordernumber']  = $orderNumberPaymentFee;
            $aItem['priceNumeric'] = $oShopgateOrder->getAmountShopPayment();
            $aItem['price']        = $oShopgateOrder->getAmountShopPayment();
            $aItem['quantity']     = 1;
            $aItem['amount']       = $aItem['price'] * $aItem['quantity'];
            $aItem['articlename']  = "Abschlag für Zahlungsart";
            $aItem['modus']        = "4";
            $aItem['taxID']        = 0;
            $aItem['tax_rate']     = "19";

            $oOrder->sBasketData["content"][] = $aItem;
        }

        return $oOrder;
    }

    /**
     * @param Order         $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return Order
     */
    protected function insertPlentyOrderData(Order $oOrder, ShopgateOrder $oShopgateOrder)
    {
        // Check if there is a plenty table
        $plentyTableName = 'plenty_order';
        if ($this->tableExists($plentyTableName)) {
            // Check if the order id has already been inserted to the plenty table
            $sql = "SELECT * FROM `{$plentyTableName}` WHERE `shopwareId` = " . $oOrder->getId();
            $row = Shopware()->Db()->fetchRow($sql);

            // Insert the order id to the plenty table since it does not exist, yet
            if (empty($row)) {
                $sql = "INSERT INTO `{$plentyTableName}` SET `shopwareId` = " . $oOrder->getId();
                Shopware()->Db()->query($sql);
            }
        }

        return $oOrder;
    }

    /**
     * @param Order         $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return Order
     */
    protected function insertShopgateOrderData(Order $oOrder, ShopgateOrder $oShopgateOrder)
    {
        $sql   = "SELECT id FROM s_shopgate_orders WHERE " . $oShopgateOrder->getOrderNumber()
            . " AND orderID=" . $oOrder->getId();
        $rowId = Shopware()->Db()->fetchOne($sql);

        // Don't insert when already existing (which can happen while repairing an order)
        if (empty($rowId)) {
            $data = new \Shopware\CustomModels\Shopgate\Order();
            $data->fromShopgateOrder($oShopgateOrder);
            $data->setOrder($oOrder);
            $data->setOrderItemMap($this->orderItemMap);

            Shopware()->Models()->persist($data);
            Shopware()->Models()->flush($data);
            $this->log(
                "insertShopgateOrderData:: Saved a new entry in the s_shopgate_orders table using the shopgate order number #"
                . $oShopgateOrder->getOrderNumber(),
                ShopgateLogger::LOGTYPE_DEBUG
            );
        } else {
            $this->log(
                "insertShopgateOrderData:: No entry created for the order using the transactionID #"
                . $oShopgateOrder->getOrderNumber() . " because it was already found.",
                ShopgateLogger::LOGTYPE_DEBUG
            );
        }

        return $oOrder;
    }

    /**
     * @param Order         $order
     * @param ShopgateOrder $shopgateOrder
     *
     * @return Order
     */
    protected function insertPaymorrowOrderData(Order $order, ShopgateOrder $shopgateOrder)
    {
        $table = self::PAYMORROW_ORDERS_TABLE;
        if ($this->isPaymorrowOrder($shopgateOrder) && $this->tableExists($table)) {
            $sql   = "SELECT id FROM $table WHERE ordernumber = " . $order->getNumber();
            $rowId = Shopware()->Db()->fetchOne($sql);

            $infos = $shopgateOrder->getPaymentInfos();
            if (empty($rowId)) {
                Shopware()->Db()->query(
                    "
					INSERT INTO $table SET
					ordernumber = ?,
					type = ?,
					transactionid = ?,
					requestid = ?,
					responseResultCode = ?,
					bic = ?,
					iban = ?,
					nationalBankName = ?,
					nationalBankCode = ?,
					nationalBankAccountNumber = ?,
					paymentReference = ?
				",
                    array(
                        $order->getNumber(),
                        $this->getShopPaymentMethodName($shopgateOrder->getPaymentMethod()),
                        $infos['pm_order_transaction_id'],
                        $infos['request_id'],
                        $infos['pm_status'],
                        $infos['bic'],
                        $infos['iban'],
                        $infos['national_bank_name'],
                        $infos['national_bank_code'],
                        $infos['national_bank_acc_num'],
                        $infos['request_id'],
                    )
                );
                $this->log(
                    __FUNCTION__ . ": $table entry created for shopgate order # " . $shopgateOrder->getOrderNumber(),
                    ShopgateLogger::LOGTYPE_DEBUG
                );
            } else {
                $this->log(
                    __FUNCTION__ . ": $table entry already existing for shopgate order # "
                    . $shopgateOrder->getOrderNumber(),
                    ShopgateLogger::LOGTYPE_DEBUG
                );
            }
        }

        return $order;
    }

    /**
     * @param Order         $order
     * @param ShopgateOrder $shopgateOrder
     *
     * @return Order
     */
    protected function insertPayolutionOrderData(Order $order, ShopgateOrder $shopgateOrder)
    {
        if ($this->isPayolutionOrder($shopgateOrder)) {
            $infos = $shopgateOrder->getPaymentInfos();

            Shopware()->Db()->query(
                "
				UPDATE s_order_attributes SET
				swp_pol_identification_uniqueid = :unique_id,
				swp_pol_identification_paymentreference = :reference_id
				WHERE orderID = {$order->getId()}
			",
                array(
                    'unique_id'    => $infos['unique_id'],
                    'reference_id' => $infos['reference_id'],
                )
            );

            Shopware()->Db()->query("UPDATE `s_order` SET temporaryID = transactionID WHERE id = {$order->getId()}");
        }

        return $order;
    }

    /**
     * Set payment instructions for PP+ orders (only invoice)
     *
     * @param Order         $order
     * @param ShopgateOrder $shopgateOrder
     *
     * @return Order
     */
    protected function insertPaypalPlusOrderData(Order $order, ShopgateOrder $shopgateOrder)
    {
        if ($this->isPaypalPlusOrder($shopgateOrder)) {
            $paymentInfos = $shopgateOrder->getPaymentInfos();
            $orderNumber  = $order->getNumber();

            if (!empty($paymentInfos['payment_info']['payment_instruction'])) {
                $instructions   = $paymentInfos['payment_info']['payment_instruction'];
                $amountValue    = $shopgateOrder->getAmountComplete();
                $amountCurrency = $shopgateOrder->getCurrency();

                $insertQuery = "INSERT INTO s_payment_paypal_plus_payment_instruction (
                      ordernumber,
                      reference_number,
                      instruction_type,
                      bank_name,
                      account_holder_name,
                      international_bank_account_number,
                      bank_identifier_code,
                      amount_value,
                      amount_currency,
                      payment_due_date
                  ) VALUES (
                      :ordernumber,
                      :reference_number,
                      :instruction_type,
                      :bank_name,
                      :account_holder_name,
                      :international_bank_account_number,
                      :bank_identifier_code,
                      :amount_value,
                      :amount_currency,
                      :payment_due_date
                );";

                $parameter = array(
                    'ordernumber'                       => $orderNumber,
                    'reference_number'                  => $instructions['reference_number'],
                    'instruction_type'                  => $instructions['instruction_type'],
                    'bank_name'                         => $instructions['recipient_banking_instruction']['bank_name'],
                    'account_holder_name'               => $instructions['recipient_banking_instruction']['account_holder_name'],
                    'international_bank_account_number' => $instructions['recipient_banking_instruction']['international_bank_account_number'],
                    'bank_identifier_code'              => $instructions['recipient_banking_instruction']['bank_identifier_code'],
                    'amount_value'                      => $amountValue,
                    'amount_currency'                   => $amountCurrency,
                    'payment_due_date'                  => $instructions['payment_due_date'],
                );

                Shopware()->Db()->query($insertQuery, $parameter);
            }
        }

        return $order;
    }

    /**
     * Set payment instructions for PP+ orders (only invoice) for the SwagPaymentPayPalUnified plugin
     *
     * @param Order         $order
     * @param ShopgateOrder $shopgateOrder
     *
     * @return Order
     */
    protected function insertPaypalUnifiedOrderData(Order $order, ShopgateOrder $shopgateOrder)
    {
        if ($this->isPaypalUnifiedOrder($shopgateOrder)) {
            $paymentInfos = $shopgateOrder->getPaymentInfos();
            $orderNumber  = $order->getNumber();

            if (!empty($paymentInfos['payment_info']['payment_instruction'])) {
                $instructions   = $paymentInfos['payment_info']['payment_instruction'];
                $amountValue    = $shopgateOrder->getAmountComplete();

                $insertQuery = "INSERT INTO swag_payment_paypal_unified_payment_instruction (
                      order_number,
                      bank_name,
                      account_holder,
                      iban,
                      bic,
                      amount,
                      reference,
                      due_date
                  ) VALUES (
                      :order_number,
                      :bank_name,
                      :account_holder,
                      :iban,
                      :bic,
                      :amount,
                      :reference,
                      :due_date
                );";

                $parameter = array(
                    'order_number'     => $orderNumber,
                    'bank_name'        => $instructions['recipient_banking_instruction']['bank_name'],
                    'account_holder'   => $instructions['recipient_banking_instruction']['account_holder_name'],
                    'iban'             => $instructions['recipient_banking_instruction']['international_bank_account_number'],
                    'bic'              => $instructions['recipient_banking_instruction']['bank_identifier_code'],
                    'amount'           => $amountValue,
                    'reference'        => $instructions['reference_number'],
                    'due_date'         => $instructions['payment_due_date'],
                );

                Shopware()->Db()->query($insertQuery, $parameter);
            }
        }

        return $order;
    }

    /**
     * Set payment instructions for SEPA orders
     *
     * @param Order         $order
     * @param ShopgateOrder $shopgateOrder
     *
     * @return Order
     */
    protected function insertSepaOrderData(Order $order, ShopgateOrder $shopgateOrder)
    {
        $paymentName = $this->getShopPaymentMethodName($shopgateOrder->getPaymentMethod());

        if ($paymentName == 'sepa') {
            $paymentInfos = $shopgateOrder->getPaymentInfos();
            $orderId      = $order->getId();
            $userId       = $this->customerId;
            $address      = $shopgateOrder->getInvoiceAddress();
            $paymentMean  = $this->getShopPaymentMethod($paymentName);
            $date         = new \DateTime();
            $data         = array(
                'payment_mean_id' => $paymentMean->getId(),
                'order_id'        => $orderId,
                'user_id'         => $userId,
                'firstname'       => $address->getFirstName(),
                'lastname'        => $address->getLastName(),
                'address'         => $address->getStreet1(),
                'zipcode'         => $address->getZipcode(),
                'city'            => $address->getCity(),
                'bank_name'       => $paymentInfos['bank_name'],
                'account_holder'  => $address->getFirstName() . " " . $address->getLastName(),
                'iban'            => $paymentInfos['iban'],
                'bic'             => $paymentInfos['bic'],
                'amount'          => $order->getInvoiceAmount(),
                'created_at'      => $date->format('Y-m-d'),
            );

            Shopware()->Db()->insert('s_core_payment_instance', $data);
        }

        return $order;
    }

    /**
     * @param Order         $order
     * @param ShopgateOrder $shopgateOrder
     *
     * @return Order
     */
    protected function insertAmazonPaymentsOrderData(Order $order, ShopgateOrder $shopgateOrder)
    {
        if ($this->isAmazonPaymentsOrder($shopgateOrder)) {
            $paymentInfos = $shopgateOrder->getPaymentInfos();

            Shopware()->Db()->query(
                "
				UPDATE s_order_attributes SET
				bestit_amazon_capture_id = :capture_id,
				bestit_amazon_authorization_id = :authorization_id
				WHERE orderID = {$order->getId()}
			",
                array(
                    'capture_id'       => isset($paymentInfos['mws_capture_id'])
                        ? $paymentInfos['mws_capture_id']
                        : '',
                    'authorization_id' => isset($paymentInfos['mws_auth_id'])
                        ? $paymentInfos['mws_auth_id']
                        : '',
                )
            );
        }

        return $order;
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return bool
     */
    protected function isAmazonPaymentsOrder(ShopgateOrder $shopgateOrder)
    {
        return $shopgateOrder->getPaymentMethod() == ShopgateOrder::AMAZON_PAYMENT
            && ($this->config->isModuleEnabled('BestitAmazonPaymentsAdvanced')
                || $this->config->isModuleEnabled(
                    'BestitAmazonPay'
                ));
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return bool
     */
    protected function isBillsafeOrder(ShopgateOrder $shopgateOrder)
    {
        return $shopgateOrder->getPaymentMethod() == ShopgateOrder::BILLSAFE
            && $this->config->isModuleEnabled('SwagPaymentBillsafe');
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return bool
     */
    protected function isPaymorrowOrder(ShopgateOrder $shopgateOrder)
    {
        return
            in_array($shopgateOrder->getPaymentMethod(), array(ShopgateOrder::PAYMRW_DBT, ShopgateOrder::PAYMRW_INV))
            && $this->config->isModuleEnabled('PiPaymorrowPayment');
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return bool
     */
    protected function isPaypalOrder(ShopgateOrder $shopgateOrder)
    {
        return $shopgateOrder->getPaymentMethod() == ShopgateOrder::PAYPAL
            && $this->config->isModuleEnabled('SwagPaymentPaypal');
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return bool
     */
    protected function isPaypalPlusOrder(ShopgateOrder $shopgateOrder)
    {
        return $shopgateOrder->getPaymentMethod() == ShopgateOrder::PPAL_PLUS
            && $this->config->isModuleEnabled('SwagPaymentPaypal')
            && $this->config->isModuleEnabled('SwagPaymentPaypalPlus');
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return bool
     */
    protected function isPaypalUnifiedOrder(ShopgateOrder $shopgateOrder)
    {
        return in_array($shopgateOrder->getPaymentMethod(), array(ShopgateOrder::PAYPAL, ShopgateOrder::PPAL_PLUS))
            && $this->config->isModuleEnabled('SwagPaymentPayPalUnified');
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return bool
     */
    protected function isSofortOrder(ShopgateOrder $shopgateOrder)
    {
        return
            $shopgateOrder->getPaymentMethod() == ShopgateOrder::SUE
            && ($this->config->isModuleEnabled('SofortPayment') || $this->config->isModuleEnabled('PaymentSofort'));
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return bool
     */
    protected function isPayolutionOrder(ShopgateOrder $shopgateOrder)
    {
        return
            in_array($shopgateOrder->getPaymentMethod(), array(ShopgateOrder::PAYOL_INS, ShopgateOrder::PAYOL_INV))
            && $this->config->isModuleEnabled('SwpPaymentPayolution');
    }

    /**
     * @param sOrder        $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return sOrder
     */
    protected function insertOrder(sOrder $oOrder, ShopgateOrder $oShopgateOrder)
    {
        $taxRates  = array();
        $amount    = 0;
        $amountNet = 0;
        $maxTax    = 0;
        foreach ($oShopgateOrder->getItems() as $item) {
            $infos = $this->jsonDecode($item->getInternalOrderInfo(), true);

            $tax = (float)$item->getTaxPercent();
            if ($tax > $maxTax) {
                $maxTax = $tax;
            }

            if (!isset($taxRates[(string)$tax])) {
                $taxRates[(string)$tax] = 0;
            }

            $price    = $item->getUnitAmountWithTax();
            $priceNet = $item->getUnitAmount();
            $quantity = $item->getQuantity();
            if ($infos["purchasesteps"]) {
                $quantity = $quantity * $infos["purchasesteps"];
                $price    = $price / $infos["purchasesteps"];
                $priceNet = $priceNet / $infos["purchasesteps"];
            }

            $amount         += $price * $quantity;
            $amountNet      += $priceNet * $quantity;
            $taxRates[(string)$tax] += $amount - $amountNet;
        }
        $shippingNet = $oShopgateOrder->getAmountShipping() / (1 + ($maxTax / 100));
        $amountNet   += $shippingNet;

        foreach ($oShopgateOrder->getExternalCoupons() as $coupon) {
            $amountNet -= $coupon->getAmountNet();
        }

        $oOrder->sAmount                  = $oShopgateOrder->getAmountComplete();
        $oOrder->sAmountWithTax           = $oShopgateOrder->getAmountComplete();
        $oOrder->sAmountNet               = round($amountNet, 2);
        $oOrder->sShippingcosts           = $oShopgateOrder->getAmountShipping();
        $oOrder->sShippingcostsNumeric    = $oShopgateOrder->getAmountShipping();
        $oOrder->sShippingcostsNumericNet = round($shippingNet, 2);

        $oOrder->bookingId = $this->getTransactionId($oShopgateOrder);

        $aBasket                             = array();
        $aBasket["Amount"]                   = $oShopgateOrder->getAmountComplete();
        $aBasket["AmountNet"]                = $amountNet;
        $aBasket["Quantity"]                 = 1;
        $aBasket["AmountNumeric"]            = $oShopgateOrder->getAmountComplete();
        $aBasket["AmountNetNumeric"]         = $amountNet;
        $aBasket["AmountWithTax"]            = $oShopgateOrder->getAmountComplete();
        $aBasket["AmountWithTaxNumeric"]     = $oShopgateOrder->getAmountComplete();
        $aBasket["sShippingcostsWithTax"]    = $oShopgateOrder->getAmountShipping();
        $aBasket["sShippingcostsNet"]        = $shippingNet;
        $aBasket["sShippingcostsDifference"] = $aBasket["sShippingcostsWithTax"] - $aBasket["sShippingcostsNet"];

        $aBasket["sShippingcostsTax"] = $maxTax;
        $aBasket["sTaxRates"]         = $taxRates;
        $aBasket["sShippingcosts"]    = $oShopgateOrder->getAmountShipping();

        $aBasket["sCurrencyId"]       = Shopware()->Shop()->getCurrency()->getId();
        $aBasket["sCurrencyFactor"]   = Shopware()->Shop()->getCurrency()->getFactor();
        $aBasket["sCurrencyName"]     = Shopware()->Shop()->getCurrency()->getCurrency();

        $oOrder->sBasketData = $aBasket;
        $oOrder->dispatchId  = null;

        /** @var ShopgateShippingInfo $sgShippingInfo */
        $sgShippingInfo = $oShopgateOrder->getShippingInfos();
        if (!empty($sgShippingInfo)
            && $oShopgateOrder->getShippingType()
            == Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::SHIPPING_SERVICE_PLUGIN_API
        ) {
            // check cart also returns the dispatchId per available dispatch
            if (strlen($sgShippingInfo->getApiResponse()) > 0) {
                $apiResponse        = $this->jsonDecode($sgShippingInfo->getApiResponse(), true);
                $oOrder->dispatchId = $apiResponse['id'];
            } else {
                // search for a valid dispatch type per shipping name
                $oOrder->dispatchId = Shopware()->Db()->fetchOne(
                    "SELECT `id` FROM `s_premium_dispatch` WHERE `name` LIKE ?",
                    $sgShippingInfo->getName()
                );
            }
        }

        // fallback functionality to fixed dispatchId if the selected one can't be found
        if (empty($oOrder->dispatchId)) {
            $oOrder->dispatchId = $this->config->getFixedShippingService();
        }

        $payment                                    =
            $this->getShopPaymentMethod($this->getShopPaymentMethodName($oShopgateOrder->getPaymentMethod()));
        $payment                                    = Shopware()->Models()->toArray($payment);
        $oOrder->sUserData["additional"]["payment"] = $payment;

        return $oOrder;
    }

    /**
     * Fills up the attributes of a customer and their related entities with default values and saves them.
     *
     * This will affect the following tables:
     * - s_user_attributes
     * - s_user_address_attributes
     * - s_user_billing_address_attributes
     * - s_user_shipping_address_attributes
     *
     * Note that the Doctrine models for those tables are created on-the-fly and reside in the var/cache/doctrine
     * folder of Shopware. So, auto-completion might not work.
     *
     * @param int $customerId
     */
    protected function _fillAttributes($customerId)
    {
        /** @var \Shopware\Models\Customer\Customer $customer */
        $customer = Shopware()->Models()->find("\Shopware\Models\Customer\Customer", $customerId);

        if (!is_object($customer)) {
            return;
        }

        // add the attributes to the customer model
        $this->_fillCustomerAttributes($customer);
        $this->_fillBillingAddressAttributes($customer);
        $this->_fillShippingAddressAttributes($customer);

        // trigger saving the attribute entities
        Shopware()->Models()->flush();
    }

    /**
     * @param \Shopware\Models\Customer\Customer $customer
     */
    protected function _fillCustomerAttributes(\Shopware\Models\Customer\Customer $customer)
    {
        if (null !== $customer->getAttribute()) {
            return;
        }

        Shopware()->Models()->persist($customer->setAttribute(new \Shopware\Models\Attribute\Customer()));
    }

    /**
     * @param \Shopware\Models\Customer\Customer $customer
     */
    protected function _fillShippingAddressAttributes(\Shopware\Models\Customer\Customer $customer)
    {
        $shipping = $this->config->assertMinimumVersion('5.5.0')
            ? $customer->getDefaultShippingAddress()
            : $customer->getShipping();

        if (!is_object($shipping) || null !== $shipping->getAttribute()) {
            return;
        }

        $customerShippingAttribute = $this->config->assertMinimumVersion('5.5.0')
            ? new \Shopware\Models\Attribute\CustomerAddress()
            : new \Shopware\Models\Attribute\CustomerShipping();

        $shipping->setAttribute($customerShippingAttribute);
        Shopware()->Models()->persist($shipping);
    }

    /**
     * @param \Shopware\Models\Customer\Customer $customer
     */
    protected function _fillBillingAddressAttributes(\Shopware\Models\Customer\Customer $customer)
    {
        $billing = $this->config->assertMinimumVersion('5.5.0')
            ? $customer->getDefaultBillingAddress()
            : $customer->getBilling();

        if (!is_object($billing) || null !== $billing->getAttribute()) {
            return;
        }

        $customerBillingAttribute = $this->config->assertMinimumVersion('5.5.0')
            ? new \Shopware\Models\Attribute\CustomerAddress()
            : new \Shopware\Models\Attribute\CustomerBilling();

        $billing->setAttribute($customerBillingAttribute);
        Shopware()->Models()->persist($billing);
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return null|int
     */
    protected function getCustomerIdForOrder($shopgateOrder)
    {
        $customerId        = null;
        $extCustomerNumber = $shopgateOrder->getExternalCustomerNumber();
        $extCustomerId     = $shopgateOrder->getExternalCustomerId();

        if (!$this->config->assertMinimumVersion('5.2.0')) {
            $idField = 'userID';
            $sql     = "
				SELECT {$idField}
				FROM s_user_billingaddress
				WHERE customernumber = '" . $extCustomerNumber . "'
				ORDER BY id DESC
			";
        } else {
            $idField = 'id';
            $sql     = "SELECT {$idField}
				FROM s_user
				WHERE customernumber = '" . $extCustomerNumber . "'
				ORDER BY id DESC";
        }

        foreach (Shopware()->Db()->fetchAll($sql) as $userData) {
            if ($extCustomerId
                && $extCustomerId == $userData[$idField]
            ) {
                $customerId = $userData[$idField];
            }
        }

        return $customerId;
    }

    /**
     * Checks if the external customer id and the mail address of the shopgate order matches to a shopware user
     *
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return bool
     */
    protected function mailAndUserIdMatch(ShopgateOrder $oShopgateOrder)
    {
        if (!$oShopgateOrder->getExternalCustomerId()) {
            return false;
        }

        /** @var \Shopware\Models\Customer\Customer $customer */
        $customer = Shopware()->Models()->find(
            '\Shopware\Models\Customer\Customer',
            $oShopgateOrder->getExternalCustomerId()
        );

        return $customer !== null && $customer->getEmail() === $oShopgateOrder->getMail();
    }

    /**
     * @param sOrder        $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return sOrder
     * @throws ShopgateLibraryException
     */
    protected function insertOrderCustomer(sOrder $oOrder, ShopgateOrder $oShopgateOrder)
    {
        // Check for customer number first
        $extCustomerNumber = $oShopgateOrder->getExternalCustomerNumber();
        if (!empty($extCustomerNumber)) {
            $this->customerId = $this->getCustomerIdForOrder($oShopgateOrder);

            // check if the customer number exists
            if (empty($this->customerId)) {
                $this->log(
                    "insertOrderCustomer:: no customer has been found for the given (connect-)customer-number [= {$extCustomerNumber}].",
                    ShopgateLogger::LOGTYPE_ERROR
                );
            }
        }
        if (empty($this->customerId) && $this->mailAndUserIdMatch($oShopgateOrder)) {
            $this->customerId = $oShopgateOrder->getExternalCustomerId();
        }
        if (empty($this->customerId)) {
            $this->customerId = $this->customerImport->createGuestCustomer($oShopgateOrder);
        }

        $this->_fillAttributes($this->customerId);

        $payment = $this->getShopPaymentMethod($this->getShopPaymentMethodName($oShopgateOrder->getPaymentMethod()));

        $aUser                  = array();
        $aUser["id"]            = $this->customerId;
        $aUser["email"]         = $oShopgateOrder->getMail();
        $aUser["active"]        = 0;
        $aUser["accountmode"]   = 1;
        $aUser["newsletter"]    = 0;
        $aUser["customergroup"] = $this->defaultCustomerGroupKey;
        $aUser["paymentpreset"] = 0;
        $aUser["language"]      = "";
        $aUser["subshopID"]     = $this->shop->getId();
        $aUser["paymentID"]     = $payment->getId();

        $oOrder->sUserData["additional"]["user"] = $aUser;

        Shopware()->Session()->sUserId = $this->customerId;

        return $oOrder;
    }

    /**
     * @param sOrder        $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return sOrder
     */
    protected function insertOrderDeliveryAddress(sOrder $oOrder, ShopgateOrder $oShopgateOrder)
    {
        return $this->insertOrderAddress($oOrder, $oShopgateOrder->getDeliveryAddress(), "shippingaddress");
    }

    /**
     * @param sOrder        $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return sOrder
     */
    protected function insertOrderInvoiceAddress(sOrder $oOrder, ShopgateOrder $oShopgateOrder)
    {
        $oOrder                                                =
            $this->insertOrderAddress($oOrder, $oShopgateOrder->getInvoiceAddress(), "billingaddress");
        $oOrder->sUserData["billingaddress"]["customernumber"] = $oShopgateOrder->getExternalCustomerNumber()
            ? $oShopgateOrder->getExternalCustomerNumber()
            : "";
        if (empty($oOrder->sUserData["billingaddress"]["phone"])) {
            $oOrder->sUserData["billingaddress"]["phone"] = $oShopgateOrder->getPhone()
                ? $oShopgateOrder->getPhone()
                : "";
        }

        return $oOrder;
    }

    /**
     * @param sOrder $oOrder
     * @param ShopgateAddress                                                   $oOrderAddress
     * @param string                                                            $type
     *
     * @return sOrder
     * @throws ShopgateLibraryException
     */
    protected function insertOrderAddress(sOrder $oOrder, ShopgateAddress $oOrderAddress, $type)
    {
        $country = $this->customerImport->getCountryByIso($oOrderAddress->getCountry());
        if (empty($country)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_UNKNOWN_COUNTRY_CODE, "insertOrderAddress: Country ISO-CODE '{$oOrderAddress->getCountry(
            )}' does not exist (address type '{$type}')", true
            );
        }
        $state = $this->customerImport->getStateByIso($oOrderAddress->getState());

        $aAddress               = array();
        $aAddress["userID"]     = $this->customerId;
        $aAddress["department"] = "";
        $aAddress["company"]    = $oOrderAddress->getCompany()
            ?: "";
        $aAddress["salutation"] = ($oOrderAddress->getGender() == ShopgateAddress::MALE)
            ? self::MALE
            : self::FEMALE;
        $aAddress["firstname"]  = $oOrderAddress->getFirstName();
        $aAddress["lastname"]   = $oOrderAddress->getLastName();
        if ($this->config->assertMinimumVersion('5.0.0')) {
            $aAddress["street"] =
                $oOrderAddress->getStreet1() . ($oOrderAddress->getStreet2()
                    ? " ({$oOrderAddress->getStreet2()})"
                    : "");
        } else {
            $aAddress["street"]       = $oOrderAddress->getStreetName1() . ($oOrderAddress->getStreet2()
                    ? " ({$oOrderAddress->getStreet2()})"
                    : "");
            $aAddress["streetnumber"] = $oOrderAddress->getStreetNumber1();
        }
        $aAddress["zipcode"]   = $oOrderAddress->getZipcode();
        $aAddress["city"]      = $oOrderAddress->getCity();
        $aAddress["countryID"] = $country->getId();
        $aAddress["stateID"]   = $state
            ? $state->getId()
            : null;
        $aAddress["fax"]       = "";
        $aAddress["ustid"]     = "";
        $aAddress["birthday"]  = "";

        if ($type == "billingaddress") {
            $aAddress["phone"]                          = "";
            $oOrder->sUserData["additional"]["country"] =
                array(
                    "countryname" => $country->getName(),
                    "id"          => $country->getId(),
                    "countryiso"  => $oOrderAddress->getCountry(),
                );
            $isEqualBilling                             = true;
            foreach ($aAddress as $key => $value) {
                if ($oOrder->sUserData["shippingaddress"][$key] != $value) {
                    $isEqualBilling = false;
                    break;
                }
            }

            $aAddress["eqalBilling"] = $isEqualBilling
                ? "1"
                : "0";
        } elseif ($type == "shippingaddress") {
            $oOrder->sUserData["additional"]["countryShipping"] =
                array(
                    "countryname" => $country->getName(),
                    "id"          => $country->getId(),
                    "countryiso"  => $oOrderAddress->getCountry(),
                );

            ### This is extracted Shopware logic to determine if taxes must be applied for the shipping
            ### address / country and user group
            ###
            ### Taken from:
            ### engine/Shopware/Controllers/Frontend/Checkout.php
            ### Shopware_Controllers_Frontend_Checkout::getUserData()
            ###
            ### As of 2016-01-26 there has been no change to that section in Shopware since:
            ### 2012-10-19 / f2e61d0d063d7921f62565aacf3c6a2781dc81d1 / "SW-4218 Fix taxfree change in basket"
            ###
            ### Changed in order to make this run in the current environment:
            ### - checking $country->getTaxFree() instead of $sTaxFree
            ### - access to $system is now $this->system
            ### - access to $userData is now $oOrder->sUserData
            $this->system->sUSERGROUPDATA = Shopware()->Db()->fetchRow(
                "
                SELECT * FROM s_core_customergroups
                WHERE groupkey = ?
            ",
                array($this->system->sUSERGROUP)
            );

            if ($country->getTaxFree()) {
                $this->system->sUSERGROUPDATA['tax']           = 0;
                $this->system->sCONFIG['sARTICLESOUTPUTNETTO'] = 1; //Old template
                Shopware()->Session()->sUserGroupData          = $this->system->sUSERGROUPDATA;
                $oOrder->sUserData['additional']['charge_vat'] = false;
                $oOrder->sUserData['additional']['show_net']   = false;
                Shopware()->Session()->sOutputNet              = true;
            } else {
                $oOrder->sUserData['additional']['charge_vat'] = true;
                $oOrder->sUserData['additional']['show_net']   = !empty($this->system->sUSERGROUPDATA['tax']);
                Shopware()->Session()->sOutputNet              = empty($this->system->sUSERGROUPDATA['tax']);
            }
            ### End of extracted Shopware logic
        }

        // set the order net / gross depending on the "charge_vat" flag is crucial to have invoice PDFs created properly
        $oOrder->sNet = !$oOrder->sUserData['additional']['charge_vat'];

        $notProcessedCustomFields = array();
        foreach ($oOrderAddress->getCustomFields() as $customField) {
            if (isset($aAddress[$customField->getInternalFieldName()])
                && empty($aAddress[$customField->getInternalFieldName()])
            ) {
                $aAddress[$customField->getInternalFieldName()] = $customField->getValue();
            } else {
                $notProcessedCustomFields[] = $customField;
            }
        }
        $oOrderAddress->setCustomFields($notProcessedCustomFields);

        $oOrder->sUserData[$type] = $aAddress;

        return $oOrder;
    }

    /**
     * @param sOrder        $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return sOrder
     */
    protected function insertOrderItems(sOrder $oOrder, ShopgateOrder $oShopgateOrder)
    {
        $orderItemMap = array();

        // put all items into the basket
        $skippedItemList = array();
        foreach ($oShopgateOrder->getItems() as $oItem) {
            /* @var $oItem ShopgateOrderItem */
            $quantity   = $oItem->getQuantity();
            $priceNet   = $oItem->getUnitAmount();
            $price      = $oItem->getUnitAmountWithTax();
            $taxRate    = $oItem->getTaxPercent();
            $itemNumber = $oItem->getItemNumberPublic();
            $info       = $this->jsonDecode($oItem->getInternalOrderInfo(), true);

            // only add basket items that have an order number
            $articleId = Shopware()->Modules()->Articles()->sGetArticleIdByOrderNumber($itemNumber);
            if (!empty($articleId)) {
                if (!empty($info['purchasesteps'])) {
                    $quantity = $quantity * $info['purchasesteps'];
                    $price    = $price / $info['purchasesteps'];
                    $priceNet = $priceNet / $info['purchasesteps'];
                }

                // method sAddArticle is documented to return void in some Shopware versions, actually returns null, bool or int.
                /** @noinspection PhpVoidFunctionResultUsedInspection */
                $insertId = Shopware()
                    ->Modules()
                    ->Basket()
                    ->sAddArticle(
                        $itemNumber,
                        $quantity
                    );

                if (empty($insertId)) {
                    $this->nonInsertableOrderItems[] = $oItem;
                    $skippedItemList[]               = $oItem;
                    continue;
                }
                $orderItemMap[(string)$insertId] = $oItem->getOrderItemId();

                // update the article price values to have the exact same price values as it has been reported by the Shopgate order
                $updateData[$insertId] = array(
                    'price'    => $this->formatPriceNumber($price),
                    'netprice' => $this->formatPriceNumber($priceNet),
                    'tax_rate' => $this->formatPriceNumber($taxRate),
                );
                Shopware()->Db()->update('s_order_basket', $updateData[$insertId], "id={$insertId}");
            } else {
                // non-article items are left outside of the basket
                // add to the list later (after the extra items like "sw-payment" and "SHIPPINGDISCOUNT" have been removed)
                $skippedItemList[] = $oItem;
            }
        }

        // remove all basket entries, that are not actual items (find by session id since the basket  id's are unknown)
        $sessionId = $this->system->sSESSION_ID;
        $sql       = "DELETE FROM `s_order_basket` WHERE `sessionID` = '{$sessionId}' AND `articleID` = 0";
        Shopware()->Db()->exec($sql);

        // load the basket
        $basket = $this->getBasket($oShopgateOrder);

        // add up data of $orderItemMap for processing after the order has been saved
        foreach ($basket['content'] as $key => $tmpArticle) {
            $basket['content'][$key]['order_item_id'] = $orderItemMap[$basket['content'][$key]['id']];

            if (!empty($updateData[$tmpArticle['id']])) {
                // update prices again AFTER basket gets Loaded by shopware
                $priceGross                              = $updateData[$tmpArticle['id']]['price'];
                $priceNet                                = $updateData[$tmpArticle['id']]['netprice'];
                $taxRate                                 = $updateData[$tmpArticle['id']]['tax_rate'];
                $quantity                                = $tmpArticle['quantity'];
                $basket['content'][$key]['amount']       = $priceGross * $quantity;
                $basket['content'][$key]['amountnet']    = $priceNet * $quantity;
                $basket['content'][$key]['price']        = $priceGross;
                $basket['content'][$key]['netprice']     = $priceNet;
                $basket['content'][$key]['priceNumeric'] = $priceGross;
                $basket['content'][$key]['tax_rate']     = $taxRate;
                $basket['content'][$key]['tax']          = $this->formatPriceNumber(
                    ($priceGross - $priceNet) * $quantity
                );

                if ((int)$updateData[$tmpArticle['id']]['tax_rate'] === 0) {
                    $basket['content'][$key]['taxID'] = 0;
                }
            }
        }

        foreach ($oShopgateOrder->getExternalCoupons() as $externalCoupon) {
            if ($externalCoupon->getCode() == $this->system->sCONFIG['sDISCOUNTNUMBER']) {
                $orderItem = new ShopgateOrderItem();
                $orderItem->setUnitAmount($externalCoupon->getAmount() * -1);
                $orderItem->setUnitAmountWithTax($externalCoupon->getAmount() * -1);
                $orderItem->setName($externalCoupon->getName());
                $orderItem->setItemNumberPublic($externalCoupon->getCode());
                $orderItem->setItemNumber($externalCoupon->getCode());
                $orderItem->setTaxPercent(0);
                $orderItem->setOrderItemId($externalCoupon->getCode());
                $orderItem->setQuantity(1);
                $skippedItemList[] = $orderItem;
            }
        }

        // add missing "virtual" items like shopgate coupons (must be added afterwards because the getBasket method does not load such basket articles)
        foreach ($skippedItemList as $oItem) {
            $priceNet     = $oItem->getUnitAmount();
            $priceGross   = $oItem->getUnitAmountWithTax();
            $quantity     = $oItem->getQuantity();
            $newOrderItem = array(
                'id'                      => -1,
                'sessionID'               => $sessionId,
                'userID'                  => $this->customerId,
                'articlename'             => $oItem->getName(),
                'articleID'               => 0,
                'ordernumber'             => $oItem->getItemNumberPublic(),
                'shippingfree'            => 0,
                'quantity'                => $quantity,
                'price'                   => $this->formatPriceNumber($priceGross),
                'netprice'                => $this->formatPriceNumber($priceNet),
                'tax_rate'                => $oItem->getTaxPercent(),
                'datum'                   => date('Y-m-d H:i:s'),
                'modus'                   => 2,
                'esdarticle'              => 0,
                'partnerID'               => 0,
                'lastviewport'            => '',
                'useragent'               => '',
                'config'                  => '',
                'currencyFactor'          => Shopware()->Shop()->getCurrency()->getFactor(),
                'liveshoppingID'          => 0,
                'bundleID'                => 0,
                'bundle_join_ordernumber' => '',
                'packunit'                => '',
                'minpurchase'             => 1,
                'taxID'                   => !empty($basket['content'][0])
                    ? $basket['content'][0]['taxID']
                    : 1,
                'instock'                 => 1,
                'suppliernumber'          => '',
                'purchasesteps'           => 1,
                'purchaseunit'            => null,
                'laststock'               => 0,
                'shippingtime'            => '',
                'releasedate'             => null,
                'sReleaseDate'            => null,
                'stockmin'                => 0,
                'itemUnit'                => null,
                'ob_attr1'                => '',
                'ob_attr2'                => null,
                'ob_attr3'                => null,
                'ob_attr4'                => null,
                'ob_attr5'                => null,
                'ob_attr6'                => null,
                'shippinginfo'            => true,
                'esd'                     => 0,
                'additional_details'      => array(),
                'amount'                  => $this->formatPriceNumber($priceGross * $quantity),
                'amountnet'               => $this->formatPriceNumber($priceNet * $quantity),
                'priceNumeric'            => $this->formatPriceNumber($priceGross),
                'image'                   => array(),
                'linkDetails'             => '',
                'linkDelete'              => '',
                'linkNote'                => '',
                'tax'                     => $this->formatPriceNumber(($priceGross - $priceNet) * $quantity),
                'order_item_id'           => $oItem->getOrderItemId(),
            );

            if ($oItem->getType() == 'item') {
                $newOrderItem['modus'] = 0;
            }

            $basket['content'][] = $newOrderItem;
        }

        // copy only cart data
        $oOrder->sBasketData["content"] = $basket['content'];

        return $oOrder;
    }

    /**
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return array
     */
    protected function getBasket(ShopgateOrder $oShopgateOrder)
    {
        $maxTax = 0;
        foreach ($oShopgateOrder->getItems() as $item) {
            if ($item->getTaxPercent() > $maxTax) {
                $maxTax = $item->getTaxPercent();
            }
        }
        $maxTax = intval($maxTax);

        $shippingcosts = array(
            'brutto'     => $this->formatPriceNumber($oShopgateOrder->getAmountShipping()),
            'netto'      => $this->formatPriceNumber($oShopgateOrder->getAmountShipping() * 100 / (100 + $maxTax)),
            'tax'        => $maxTax, // this field seems not to be set in the original getBasket method
            'difference' => array('float' => null), // this field seems not to be set in the original getBasket method
        );

        // same code as in frontend-checkout-getBasket method
        $basket = Shopware()->Modules()->Basket()->sGetBasket();

        $basket['sShippingcostsWithTax'] = $shippingcosts['brutto'];
        $basket['sShippingcostsNet']     = $shippingcosts['netto'];
        $basket['sShippingcostsTax']     = $shippingcosts['tax'];

        if (!empty($shippingcosts['brutto'])) {
            $basket['AmountNetNumeric']         += $shippingcosts['netto'];
            $basket['AmountNumeric']            += $shippingcosts['brutto'];
            $basket['sShippingcostsDifference'] = $shippingcosts['difference']['float'];
        }
        if (!empty($basket['AmountWithTaxNumeric'])) {
            $basket['AmountWithTaxNumeric'] += $shippingcosts['brutto'];
        }
        if ((!Shopware()->System()->sUSERGROUPDATA['tax'] && Shopware()->System()->sUSERGROUPDATA['id'])) {
            $basket['sTaxRates'] = $this->getBasketTaxRates($basket);

            $basket['sShippingcosts'] = $shippingcosts['netto'];
            $basket['sAmount']        = round($basket['AmountNetNumeric'], 2);
            $basket['sAmountTax']     = round($basket['AmountWithTaxNumeric'] - $basket['AmountNetNumeric'], 2);
            $basket['sAmountWithTax'] = round($basket['AmountWithTaxNumeric'], 2);
        } else {
            $basket['sTaxRates'] = $this->getBasketTaxRates($basket);

            $basket['sShippingcosts'] = $shippingcosts['brutto'];
            $basket['sAmount']        = $basket['AmountNumeric'];

            $basket['sAmountTax'] = round($basket['AmountNumeric'] - $basket['AmountNetNumeric'], 2);
        }

        // now keep only the items that are actually real items (article-id nonzero)
        $itemBlacklist = array();
        foreach ($basket['content'] as $key => $basketItem) {
            if (empty($basketItem['articleID'])) {
                $itemBlacklist[] = $key;
            }
        }
        foreach ($itemBlacklist as $itemBlacklistKey) {
            unset($basket['content'][$itemBlacklistKey]);
        }

        return $basket;
    }

    /**
     * Exact same method as Shopware-Frontend-Checkount "getTaxRates"
     *
     * @param Shopware -Basket $basket
     *
     * @return array with tax rates
     */
    protected function getBasketTaxRates($basket)
    {
        $result = array();

        if (!empty($basket['sShippingcostsTax'])) {
            $basket['sShippingcostsTax'] = number_format(floatval($basket['sShippingcostsTax']), 2);

            $result[$basket['sShippingcostsTax']] = $basket['sShippingcostsWithTax'] - $basket['sShippingcostsNet'];
            if (empty($result[$basket['sShippingcostsTax']])) {
                unset($result[$basket['sShippingcostsTax']]);
            }
        } elseif ($basket['sShippingcostsWithTax']) {
            $result[number_format(floatval(Shopware()->Config()->get('sTAXSHIPPING')), 2)] =
                $basket['sShippingcostsWithTax'] - $basket['sShippingcostsNet'];
            if (empty($result[number_format(floatval(Shopware()->Config()->get('sTAXSHIPPING')), 2)])) {
                unset($result[number_format(floatval(Shopware()->Config()->get('sTAXSHIPPING')), 2)]);
            }
        }

        if (empty($basket['content'])) {
            ksort($result, SORT_NUMERIC);

            return $result;
        }

        foreach ($basket['content'] as $item) {
            if (!empty($item["tax_rate"])) {
                // nothing to do
            } elseif (!empty($item['taxPercent'])) {
                $item['tax_rate'] = $item["taxPercent"];
            } elseif ($item['modus'] == 2) {
                // Ticket 4842 - dynamic tax-rates
                $resultVoucherTaxMode = Shopware()->Db()->fetchOne(
                    "SELECT taxconfig FROM s_emarketing_vouchers WHERE ordercode=?",
                    array($item["ordernumber"])
                );
                // Old behaviour
                if (empty($resultVoucherTaxMode) || $resultVoucherTaxMode == "default") {
                    $tax = Shopware()->Config()->get('sVOUCHERTAX');
                } elseif ($resultVoucherTaxMode == "auto") {
                    // Automatically determinate tax
                    $tax = $this->system->sMODULES['sBasket']->getMaxTax();
                } elseif ($resultVoucherTaxMode == "none") {
                    // No tax
                    $tax = "0";
                } elseif (intval($resultVoucherTaxMode)) {
                    // Fix defined tax
                    $tax = Shopware()->Db()->fetchOne(
                        "SELECT tax FROM s_core_tax WHERE id = ?",
                        array($resultVoucherTaxMode)
                    );
                }
                $item['tax_rate'] = $tax;
            } else {
                // Ticket 4842 - dynamic tax-rates
                $taxAutoMode = Shopware()->Config()->get('sTAXAUTOMODE');
                if (!empty($taxAutoMode)) {
                    $tax = $this->system->sMODULES['sBasket']->getMaxTax();
                } else {
                    $tax = Shopware()->Config()->get('sDISCOUNTTAX');
                }
                $item['tax_rate'] = $tax;
            }

            if (empty($item['tax_rate']) || empty($item["tax"])) {
                continue; // Ignore 0 % tax
            }

            $taxKey = number_format(floatval($item['tax_rate']), 2);

            $result[$taxKey] += str_replace(',', '.', $item['tax']);
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * @param sOrder        $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return sOrder
     */
    protected function insertOrderExternalCoupons(sOrder $oOrder, ShopgateOrder $oShopgateOrder)
    {
        foreach ($oShopgateOrder->getExternalCoupons() as $coupon) {
            if ($coupon->getCode() == $this->system->sCONFIG['sDISCOUNTNUMBER']) {
                continue;
            }
            $info = $this->jsonDecode($coupon->getInternalInfo(), true);

            $aItem = array();
            $aItem['id'] = -1;

            // in case of individual it has to be code_id, otherwise voucher_id
            $aItem['articleID'] = !is_null($info['code_id'])
                ? $info['code_id']
                : $info["voucher_id"];

            $aItem['ordernumber'] = $info["order_code"];

            $tax      = $info["tax_percent"];
            $price    = abs($coupon->getAmount()) * -1; // be sure that coupon amount is negative!
            $priceNet = $price / (1 + ($tax / 100));

            $aItem['netprice']     = $this->formatPriceNumber($priceNet);
            $aItem['price']        = $this->formatPriceNumber($price);
            $aItem['priceNumeric'] = $price;
            $aItem['amountnet']    = $this->formatPriceNumber($priceNet);
            $aItem['amount']       = $this->formatPriceNumber($price);
            $aItem['quantity']     = 1;
            $aItem['articlename']  = "Gutschein";
            $aItem['status']       = 0;

            if ($aItem['ordernumber'] == $this->system->sCONFIG['sDISCOUNTNUMBER']) {
                $aItem['modus'] = 3;
            } else {
                $aItem['modus'] = 2;
            }
            $aItem['tax_rate'] = $tax;
            $aItem['taxID']    = 0;

            $oOrder->sBasketData["content"][] = $aItem;

            if ($info["code_id"]) {
                $code = Shopware()->Models()->find('\Shopware\Models\Voucher\Code', $info["code_id"]);
                $code->setCustomerId($oOrder->sUserData["additional"]["user"]["id"]);
                Shopware()->Models()->flush($code);
            }
        }

        return $oOrder;
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getShopPaymentMethod($name)
    {
        $dql     = "SELECT p FROM \Shopware\Models\Payment\Payment p WHERE p.name = :payment";
        $payment = Shopware()->Models()->createQuery($dql)
            ->setMaxResults(1)
            ->setParameter("payment", $name)
            ->getOneOrNullResult();

        if ($payment === null && $name !== self::DEFAULT_PAYMENT_METHOD) {
            return $this->getShopPaymentMethod(self::DEFAULT_PAYMENT_METHOD);
        }

        return $payment;
    }

    /**
     * @param string $default
     * @param string $pattern
     *
     * @return string
     */
    protected function getCorrectPaymentNameFromPattern($default, $pattern)
    {
        $dql = "SELECT p.name FROM \Shopware\Models\Payment\Payment p WHERE p.name LIKE :namepat";
        $nameResult = Shopware()->Models()->createQuery($dql)
            ->setMaxResults(1)
            ->setParameter("namepat", $pattern)
            ->getOneOrNullResult();

        if (!empty($nameResult['name'])) {
            return $nameResult['name'];
        }
        return $default;
    }

    /**
     * @param string $paymentMethod
     * @param string $fallbackPaymentMethodName
     *
     * @return string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getShopPaymentMethodName($paymentMethod, $fallbackPaymentMethodName = self::DEFAULT_PAYMENT_METHOD)
    {
        switch ($paymentMethod) {
            case ShopgateOrder::SHOPGATE:
                return "shopgate";
            case ShopgateOrder::DEBIT:
                $debit = $this->getShopPaymentMethod('debit');
                $sepa  = $this->getShopPaymentMethod('sepa');
                if ($sepa->getName() == 'sepa'
                    && $sepa->getActive() == 1
                    && ($debit->getName() != 'debit'
                        || $debit->getActive() != 1
                    )
                ) {
                    return "sepa";
                }

                return "debit";
            case ShopgateOrder::PREPAY:
                return "prepayment";
            case ShopgateOrder::COD:
                return "cash";
            case ShopgateOrder::INVOICE:
                return "invoice";
            case ShopgateOrder::PAYPAL:
                if ($this->config->isModuleEnabled('SwagPaymentPayPalUnified')) {
                    return "SwagPaymentPayPalUnified";
                }
                return $this->config->isModuleEnabled('SwagPaymentPaypal')
                    ? "paypal"
                    : $fallbackPaymentMethodName;
            case ShopgateOrder::PPAL_PLUS:
                if ($this->config->isModuleEnabled('SwagPaymentPayPalUnified')) {
                    return "SwagPaymentPayPalUnified";
                }
                return (
                    $this->config->isModuleEnabled('SwagPaymentPaypal')
                    && $this->config->isModuleEnabled(
                        'SwagPaymentPaypalPlus'
                    )
                )
                    ? "paypal"
                    : $fallbackPaymentMethodName;
            case ShopgateOrder::BILLSAFE:
                return $this->config->isModuleEnabled('SwagPaymentBillsafe')
                    ? "billsafe_invoice"
                    : $fallbackPaymentMethodName;
            case ShopgateOrder::PAYMRW_DBT:
                return $this->config->isModuleEnabled('PiPaymorrowPayment')
                    ? "PaymorrowDebit"
                    : $fallbackPaymentMethodName;
            case ShopgateOrder::PAYMRW_INV:
                return $this->config->isModuleEnabled('PiPaymorrowPayment')
                    ? "PaymorrowInvoice"
                    : $fallbackPaymentMethodName;
            case ShopgateOrder::AMAZON_PAYMENT:
                return ($this->config->isModuleEnabled('BestitAmazonPaymentsAdvanced')
                    || $this->config->isModuleEnabled('BestitAmazonPay'))
                    ? $this->getCorrectPaymentNameFromPattern('amazon_payments_advanced', 'amazon_pay%')
                    : $fallbackPaymentMethodName;
            case ShopgateOrder::SUE:
                return ($this->config->isModuleEnabled('SofortPayment')
                    || $this->config->isModuleEnabled('PaymentSofort'))
                || $this->config->isModuleEnabled('SofagPayment')
                    ? "sofortbanking"
                    : $fallbackPaymentMethodName;
            case ShopgateOrder::PAYOL_INS:
                return $this->config->isModuleEnabled('SwpPaymentPayolution')
                    ? 'SwpPaymentPayolution_installment'
                    : $fallbackPaymentMethodName;
            case ShopgateOrder::PAYOL_INV:
                return $this->config->isModuleEnabled('SwpPaymentPayolution')
                    ? 'SwpPaymentPayolution_invoice'
                    : $fallbackPaymentMethodName;
        }

        return $fallbackPaymentMethodName;
    }

    /**
     * @param Order         $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return Order
     */
    protected function setOrderClear(\Shopware\Models\Order\Order $oOrder, ShopgateOrder $oShopgateOrder)
    {
        $iStatus = self::ORDER_PAYMENT_STATUS_OPEN;
        $sDate   = null;

        if ($oShopgateOrder->getIsPaid()) {
            $iStatus = self::ORDER_PAYMENT_STATUS_FULLY_PAID;
            $sDate   = $oShopgateOrder->getPaymentTime("Y-m-d H:i:s");
        }

        if ($this->isPaymorrowOrder($oShopgateOrder)) {
            $paymentInformation = $oShopgateOrder->getPaymentInfos();
            $paymentStatusId    = Shopware()->Db()->fetchOne(
                "SELECT id FROM s_core_states WHERE description like ?",
                array('%' . $paymentInformation['status'] . '%')
            );
            $iStatus             = !empty($paymentStatusId)
                ? $paymentStatusId
                : $iStatus;
        } elseif ($this->isBillsafeOrder($oShopgateOrder)) {
            $billsafeStatusId = Shopware()->Plugins()->Frontend()->SwagPaymentBillsafe()->Config()->paymentStatusId;
            $iStatus          = !empty($billsafeStatusId)
                ? $billsafeStatusId
                : self::ORDER_PAYMENT_STATUS_OPEN;
        } elseif ($this->isPayolutionOrder($oShopgateOrder)) {
            $subpay             = $oShopgateOrder->getPaymentMethod() == ShopgateOrder::PAYOL_INS
                ? 'PAYOLUTION_INS'
                : 'PAYOLUTION_INVOICE';
            $payolutionStatusId =
                Shopware_Plugins_Frontend_SwpPaymentPayolution_Bootstrap::getPaymentStatusFromSubpayment($subpay);
            $iStatus            = !empty($payolutionStatusId)
                ? $payolutionStatusId
                : self::ORDER_PAYMENT_STATUS_OPEN;
        }

        $sql = "UPDATE `s_order` SET cleared=" . $iStatus . (!empty($sDate)
                ? (", cleareddate='" . $sDate . "'")
                : (""))
            . " WHERE id=" . $oOrder->getId();
        Shopware()->Db()->query($sql);

        return $oOrder;
    }

    /**
     * @param Order         $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return Order
     * @throws ShopgateLibraryException
     */
    protected function setOrderStatus(\Shopware\Models\Order\Order $oOrder, ShopgateOrder $oShopgateOrder)
    {
        if (!empty($this->unfinishedOrderData)) {
            // Check if the status is 0 or 8 which means it is a default status and has not been changed, yet!
            if ($this->unfinishedOrderData['status'] != self::ORDER_STATUS_CLARIFICATION_NEEDED
                && $this->unfinishedOrderData['status'] != self::ORDER_STATUS_OPEN
            ) {
                return $oOrder;
            }
        }

        $iStatus = self::ORDER_STATUS_CLARIFICATION_NEEDED;

        if (!$oShopgateOrder->getIsShippingBlocked()) {
            $iStatus = $this->config->getShippingNotBlockedStatus();
            $this->log(
                "setOrderStatus:: Shipping is not blocked. Using status id #{$iStatus}",
                ShopgateLogger::LOGTYPE_DEBUG
            );
        } else {
            $this->log(
                "setOrderStatus:: Shipping is blocked. Using status id #{$iStatus}",
                ShopgateLogger::LOGTYPE_DEBUG
            );
        }

        if (!is_numeric($iStatus)) {
            throw new ShopgateLibraryException(
                "setOrderStatus: An invalid order status has been selected: {$iStatus}", true
            );
        }

        $sql = "UPDATE `s_order` SET status=" . $iStatus . " WHERE id=" . $oOrder->getId();
        Shopware()->Db()->query($sql);

        return $oOrder;
    }

    /**
     * @param Order         $oOrder
     * @param ShopgateOrder $oShopgateOrder
     */
    protected function updateOrderAddresses(\Shopware\Models\Order\Order $oOrder, ShopgateOrder $oShopgateOrder)
    {
        $oOrder->getShipping()->setCustomer($oOrder->getCustomer());
        $oOrder->getBilling()->setCustomer($oOrder->getCustomer());
    }

    /**
     * @param Order         $order
     * @param ShopgateOrder $shopgateOrder
     */
    protected function setOrderInternalComment(\Shopware\Models\Order\Order $order, ShopgateOrder $shopgateOrder)
    {
        $internalComment = '';
        if ($shopgateOrder->getIsTest()) {
            $internalComment = "### This is a Shopgate test-order ###\n\n";
        }

        $internalComment .= $this->arrayToString($shopgateOrder->getPaymentInfos()) . "\n";

        if ($shopgateOrder->getPaymentTransactionNumber()) {
            $internalComment =
                "Transaktions ID: {$shopgateOrder->getPaymentTransactionNumber()}\n\n" . $internalComment;
        }

        $this->updateInternalOrderComment($internalComment, $order->getId());
    }

    /**
     * @param array $array
     * @param int   $level
     *
     * @return string
     */
    private function arrayToString(array $array, $level = 0)
    {
        $result = '';
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                $jsonDecoded = json_decode($value, true);
                if (is_array($jsonDecoded)) {
                    $value = $jsonDecoded;
                }
            }
            $result .= str_repeat('    ', $level) . "$key: ";
            if (is_array($value)) {
                $result .= "\n" . $this->arrayToString($value, $level + 1);
            } else {
                $result .= "$value\n";
            }
        }

        return $result;
    }

    /**
     * @param Order         $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return Order
     */
    protected function setOrderPayment($oOrder, ShopgateOrder $oShopgateOrder)
    {
        switch ($oShopgateOrder->getPaymentMethod()) {
            case ShopgateOrder::DEBIT:
                $this->setOrderPaymentUserDebit($oOrder, $oShopgateOrder);
                break;
        }

        return $oOrder;
    }

    /**
     * @param Order         $oOrder
     * @param ShopgateOrder $oShopgateOrder
     */
    protected function setOrderCustomFields(\Shopware\Models\Order\Order $oOrder, ShopgateOrder $oShopgateOrder)
    {
        $customerComment   = "";
        $orderCustomFields = $oShopgateOrder->getCustomFields();
        $hasNewFields      = false;
        $fieldTexts        = "";

        // Fields from the Order
        $tmpFieldTexts = (count($orderCustomFields) > 0)
            ? "Zusatzfeld(er) zu der Bestellung:\n"
            : "";
        foreach ($orderCustomFields as $orderCustomField) {
            if ($orderCustomField->getInternalFieldName() === "customer_comment") {
                $customerComment .= "Kundenkommentar zu der Bestellung:\n";
                $customerComment .= $orderCustomField->getLabel() . ":" . $orderCustomField->getValue() . "\n";
                $customerComment .= "\n\n";
                continue;
            }
            $hasNewFields  = true;
            $tmpFieldTexts .= $orderCustomField->getLabel() . ":" . $orderCustomField->getValue() . "\n";
        }
        if ($hasNewFields) {
            $fieldTexts .= $tmpFieldTexts;
        }

        // Fields from the Delivery
        $hasNewFields    = false;
        $deliveryAddress = $oShopgateOrder->getDeliveryAddress();
        if (!empty($deliveryAddress)) {
            $deliveryFields = $deliveryAddress->getCustomFields();
            $tmpFieldTexts  = (count($deliveryFields) > 0)
                ? "Zusatzfeld(er) zu der Lieferadresse:\n"
                : "";
            foreach ($deliveryFields as $deliveryField) {
                if ($deliveryField->getInternalFieldName() === "customer_comment") {
                    $customerComment .= "Kundenkommentar zu der Lieferadresse:\n";
                    $customerComment .= $deliveryField->getLabel() . ":" . $deliveryField->getValue() . "\n";
                    $customerComment .= "\n\n";
                    continue;
                }
                $hasNewFields  = true;
                $tmpFieldTexts .= $deliveryField->getLabel() . ":" . $deliveryField->getValue() . "\n";
            }
        }
        if ($hasNewFields) {
            $fieldTexts .= $tmpFieldTexts;
        }

        // Fields from the Invoice
        $hasNewFields   = false;
        $invoiceAddress = $oShopgateOrder->getInvoiceAddress();
        if (!empty($invoiceAddress)) {
            $invoiceFields = $invoiceAddress->getCustomFields();
            $tmpFieldTexts = (count($invoiceFields) > 0)
                ? "Zusatzfeld(er) zu der Rechnungsadresse:\n"
                : "";
            foreach ($invoiceFields as $invoiceField) {
                if ($invoiceField->getInternalFieldName() === "customer_comment") {
                    $customerComment .= "Kundenkommentar zu der Rechnungsadresse:\n";
                    $customerComment .= $invoiceField->getLabel() . ":" . $invoiceField->getValue() . "\n";
                    $customerComment .= "\n\n";
                    continue;
                }
                $tmpFieldTexts .= $invoiceField->getLabel() . ":" . $invoiceField->getValue() . "\n";
            }
        }
        if ($hasNewFields) {
            $fieldTexts .= $tmpFieldTexts;
        }

        if (!empty($customerComment)) {
            $this->updateCustomerComment($customerComment, $oOrder->getId());
        }

        if (!empty($fieldTexts)) {
            $this->updateInternalOrderComment($fieldTexts, $oOrder->getId());
        }
    }

    /**
     * insert internal order comment into the database
     *
     * @param string $comment
     * @param int    $orderId
     */
    protected function updateCustomerComment($comment, $orderId)
    {
        $sql = "UPDATE `s_order` SET customercomment = concat(customercomment, " . Shopware()->Db()->quote($comment)
            . ") WHERE id=" . $orderId;
        Shopware()->Db()->query($sql);
    }

    /**
     * insert internal order comment into the database
     *
     * @param string $comment
     * @param int    $orderId
     */
    protected function updateInternalOrderComment($comment, $orderId)
    {
        $sql = "UPDATE `s_order` SET internalcomment = concat(internalcomment, " . Shopware()->Db()->quote($comment)
            . ") WHERE id=" . $orderId;
        Shopware()->Db()->query($sql);
    }

    /**
     * @param Order         $oOrder
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return Order
     */
    protected function setOrderPaymentUserDebit($oOrder, ShopgateOrder $oShopgateOrder)
    {
        $aPaymentInfos = $oShopgateOrder->getPaymentInfos();
        $paymentName   = $oOrder->sUserData["additional"]["payment"]['name'];
        $userId        = $this->customerId;

        if ($paymentName == 'sepa') {
            $paymentData = Shopware()->Models()->getRepository('\Shopware\Models\Customer\PaymentData')
                ->getCurrentPaymentDataQueryBuilder($userId, 'sepa')->getQuery()
                ->getOneOrNullResult();

            $paymentMean = Shopware()->Models()->getRepository('\Shopware\Models\Payment\Payment')->getActivePaymentsQuery(
                array('name' => 'Sepa')
            )->getOneOrNullResult();

            $data = array(
                'use_billing_data' => 0,
                'bankname'         => $aPaymentInfos['bank_name'],
                'iban'             => $aPaymentInfos['iban'],
                'bic'              => $aPaymentInfos['bic'],
            );

            if (!$paymentData) {
                $date                    = new \DateTime();
                $data['created_at']      = $date->format('Y-m-d');
                $data['payment_mean_id'] = $paymentMean['id'];
                $data['user_id']         = $userId;
                Shopware()->Db()->insert("s_core_payment_data", $data);
            } else {
                $where = array(
                    'payment_mean_id = ?' => $paymentMean['id'],
                    'user_id = ?'         => $userId,
                );
                Shopware()->Db()->update("s_core_payment_data", $data, $where);
            }
        } else {
            $customer = Shopware()->Models()
                ->getRepository("\Shopware\Models\Customer\Customer")
                ->findOneBy(array("id" => $userId));

            $oUserDebit = new \Shopware\Models\Customer\Debit();
            $oUserDebit->setCustomer($customer);
            $oUserDebit->setAccount($aPaymentInfos["bank_account_number"]);
            $oUserDebit->setAccountHolder($aPaymentInfos["bank_account_holder"]);
            $oUserDebit->setBankCode($aPaymentInfos["bank_code"]);
            $oUserDebit->setBankName($aPaymentInfos["bank_name"]);

            Shopware()->Models()->persist($oUserDebit);
            Shopware()->Models()->flush($oUserDebit);
        }

        return $oOrder;
    }

    /**
     * @param ShopgateOrder $oShopgateOrder
     *
     * @return mixed
     */
    protected function _checkOrderExist(ShopgateOrder $oShopgateOrder)
    {
        $sql    = "
			SELECT orderID
			FROM s_shopgate_orders
			WHERE shopgate_order_number = '{$oShopgateOrder->getOrderNumber()}'
		";
        $result = Shopware()->Db()->fetchOne($sql);

        return $result;
    }

    ############################################################################
    ## CART / COUPONS                                                         ##
    ############################################################################

    public function redeemCoupons(ShopgateCart $cart)
    {
        $coupons = array();
        foreach ($cart->getExternalCoupons() as $coupon) {
            $info = $this->jsonDecode($coupon->getInternalInfo(), true);

            // Set code as cashed if given
            if ($info["code_id"]) {
                $code = Shopware()->Models()->find('\Shopware\Models\Voucher\Code', $info["code_id"]);

                if ($code->getCashed()) {
                    throw new ShopgateLibraryException(ShopgateLibraryException::COUPON_CODE_NOT_VALID);
                }

                $code->setCashed(1);
                Shopware()->Models()->flush($code);
            }

            $coupon->setIsValid(true);
            $coupons[] = $coupon;
        }

        return $coupons;
    }

    /**
     * get the customer group key, taken from the actual user.
     * if no user could be found, the default value will be used.
     *
     * @param $uId
     *
     * @return ShopgateCartCustomer
     */
    private function getGroupDataToCustomer($uId)
    {
        $customer        = new ShopgateCartCustomer();
        $sgCustomerGroup = new ShopgateCartCustomerGroup();
        $customerGroupId = null;

        if (!empty($uId)) {
            $swCustomer =
                Shopware()
                    ->Models()
                    ->getRepository("\Shopware\Models\Customer\Customer")
                    ->findOneBy(array("id" => $uId));

            if (!empty($swCustomer)) {
                $customerGroupId = $swCustomer->getGroup()->getId();
            }
        }

        if (empty($customerGroupId)) {
            $customerGroupId = $this->defaultCustomerGroupId;
        }

        $sgCustomerGroup->setId($customerGroupId);
        $customer->setCustomerGroups(array($sgCustomerGroup));

        return $customer;
    }

    public function checkCart(ShopgateCart $cart)
    {
        $sql       = "SELECT customergroup FROM `s_user` WHERE id = ? AND accountmode = 0";
        $userData  = Shopware()->Db()->fetchRow($sql, array($cart->getExternalCustomerId()));
        $groupKey  = isset($userData['customergroup']) ? $userData['customergroup'] : 'EK';
        $sql       = "SELECT * FROM `s_core_customergroups` WHERE `groupkey` = ?";
        $groupData = Shopware()->Db()->fetchRow($sql, array($groupKey));

        Shopware()->Session()->sUserId        = $cart->getExternalCustomerId();
        Shopware()->Session()->sUserGroup     = $groupData['groupkey'];
        Shopware()->Session()->sUserGroupData = $groupData;

        $userDeliveryAddress = $cart->getDeliveryAddress();
        if (!empty($userDeliveryAddress)
            && ($userCountry = $userDeliveryAddress->getCountry())
            // assignment on purpose to make sure the same country is used in every place
            && strlen($userCountry) > 0
        ) {
            $country       = $this->customerImport->getCountryByIso($userDeliveryAddress->getCountry());
            $state         = $this->customerImport->getStateByIso($userDeliveryAddress->getState());
            $userCountryId = $country->getId();
            $userAreaId    = $country->getArea()->getId();
            $userStateId   = $state ? $state->getId() : null;
            $taxFree       = $country->getTaxFree();

            if (Shopware()->Session()->sCountry != $userCountryId
                || Shopware()->Session()->sState != $userStateId
                || Shopware()->Session()->sArea != $userAreaId
            ) {
                Shopware()->Session()->sCountry = $userCountryId;
                Shopware()->Session()->sState   = $userStateId;
                Shopware()->Session()->sArea    = $userAreaId;
                $version                        = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Version();
                if ($version->assertMinimum('5.0.0')) {
                    Shopware()->Container()->get('shopware_storefront.context_service')->initializeShopContext();
                }
            }

            if ($taxFree) {
                $groupData['tax'] = 0;
            }

            // check for existing non guest accounts
            $addressQuery           = "SELECT * FROM `s_user_shippingaddress` WHERE userID = ?";
            $currentShippingAddress = Shopware()->Db()->fetchRow($addressQuery, array($cart->getExternalCustomerId()));


            if (!empty($currentShippingAddress)) {
                $this->customerImport->updateShippingAddress($currentShippingAddress, $cart->getDeliveryAddress());
            }
        }

        $basket = Shopware()->Modules()->Basket();
        if (!empty($groupData)) {
            $basket->sSYSTEM->sUSERGROUP     = $groupData['groupkey'];
            $basket->sSYSTEM->sUSERGROUPDATA = $groupData;
        }

        $result = array(
            "currency"         => $this->system->sCurrency['currency'],
            "external_coupons" => array(),
            "shipping_methods" => array(),
            "payment_methods"  => array(),
            "items"            => $this->validateItems($cart, $basket),
            "customer"         => $this->getGroupDataToCustomer($cart->getExternalCustomerId()),
        );

        // trigger item price calculation by calling $basket->sGetBasket() before coupon evaluation
        $basketArray = $basket->sGetBasket();

        $couponCount = 0;
        // Add external coupons
        foreach ($cart->getExternalCoupons() as $_coupon) {
            $code = $_coupon->getCode();
            if ($code == $this->system->sCONFIG['sDISCOUNTNUMBER']) {
                $_coupon->setIsValid(false);
                $voucherResult = $basket->sAddVoucher($code);
                if (is_array($voucherResult)) {
                    if (isset($voucherResult["sErrorFlag"]) && $voucherResult["sErrorFlag"] === true) {
                        $message = implode("; ", $voucherResult["sErrorMessages"]);
                        $_coupon->setNotValidMessage($message);
                    }
                }
                $result["external_coupons"][$code] = $_coupon;
                continue;
            }
            $coupon = new ShopgateExternalCoupon();
            $coupon->setCode($code);

            $voucherResult = $basket->sAddVoucher($code);
            $loadedVoucher = $this->loadVoucher($coupon->getCode());
            /** @var \Shopware\Models\Voucher\Voucher $voucher */
            $voucher     = $loadedVoucher["voucher"];
            $voucherCode = $loadedVoucher["code"];
            $amount      = is_object($voucher)
                ? abs($this->getBasketDiscountAmount($voucher->getOrdercode()))
                : 0;
            if ($voucherResult === true || $amount > 0) {
                $info                = array();
                $info["voucher_id"]  = $voucher->getId();
                $info["code_id"]     = $voucherCode
                    ? $voucherCode->getId()
                    : null;
                $info["order_code"]  = $voucher->getOrdercode();
                $info["tax_id"]      = $voucher->getTaxConfig();
                $info["tax_percent"] = $basket->getMaxTax();
                $info["amount"]      = $amount;

                /** @var Enlight_Event_EventManager $eventManager */
                $eventManager = Shopware()->Events();
                $info         = $eventManager->filter(
                    'Shopgate_CheckCart_Voucher_FilterInfo',
                    $info,
                    array(
                        'subject' => $this,
                        'cart'    => $cart,
                        'code'    => $code,
                    )
                );

                $coupon->setIsValid(true);
                $coupon->setCode(
                    $voucherCode
                        ? $coupon->getCode()
                        : $voucher->getVoucherCode()
                );
                $coupon->setName($voucher->getDescription());
                $coupon->setIsFreeShipping($voucher->getShippingfree());
                $coupon->setAmount($info["amount"]);
                $coupon->setCurrency($this->system->sCurrency['currency']);
                $coupon->setInternalInfo($this->jsonEncode($info));
                $couponCount++;
            } else {
                $coupon->setIsValid(false);

                if (is_array($voucherResult)) {
                    if (isset($voucherResult["sErrorFlag"]) && $voucherResult["sErrorFlag"] === true) {
                        $message = implode("; ", $voucherResult["sErrorMessages"]);

                        $coupon->setNotValidMessage($message);
                    }
                }
            }

            $result["external_coupons"][] = $coupon;
        }

        if ($couponCount > 1) {
            throw new ShopgateLibraryException(ShopgateLibraryException::COUPON_TOO_MANY_COUPONS);
        }

        // add basket data to session in order to be risk management compliant
        $basketAmount                          = $basket->sGetAmount();
        Shopware()->Session()->sBasketQuantity = $basket->sCountBasket();
        Shopware()->Session()->sBasketAmount   = empty($basketAmount) ? 0 : array_shift($basketAmount);

        $paymentMethods = Shopware()->Modules()->Admin()->sGetPaymentMeans();
        $sUserData      = Shopware()->Modules()->Admin()->sGetUserData();

        if (!empty($sUserData["additional"]["user"]["paymentpreset"])) {
            $presetPaymentId = $sUserData["additional"]["user"]["paymentpreset"];
            $presetMethod    = $this->getPaymentMethod($paymentMethods, $presetPaymentId);
            if ($presetMethod === false) {
                $paymentMethodsAdditional = Shopware()->Modules()->Admin()->sGetPaymentMeans();
                $presetMethod             = $this->getPaymentMethod($paymentMethodsAdditional, $presetPaymentId);
                if ($presetMethod !== false) {
                    $paymentMethods[] = $presetMethod;
                }
            }
        }

        foreach ($paymentMethods as $paymentMethod) {
            $surchargeGross = 0.00;
            $surchargeNet   = 0.00;
            $tax            = 0;

            if (!empty($paymentMethod['surcharge'])) {
                $surchargeGross = $paymentMethod['surcharge'];
                $tax            = $this->fetchPaymentTaxFromBasket($basket);
                $surchargeNet   = $this->calculateNetPaymentCosts($surchargeGross, $tax);
            } elseif (!empty($paymentMethod['debit_percent'])) {
                $amount         = $basket->sGetAmount();
                $surchargeGross = $amount['totalAmount'] / 100 * $paymentMethod['debit_percent'];
                $tax            = $this->fetchPaymentTaxFromBasket($basket);
                $surchargeNet   = $this->calculateNetPaymentCosts($surchargeGross, $tax);
            }

            $method = new ShopgatePaymentMethod();
            $method->setId($paymentMethod['name']);
            $method->setAmountWithTax($this->formatPriceNumber($surchargeGross, 2));
            $method->setAmount($this->formatPriceNumber($surchargeNet, 2));
            $method->setTaxPercent($tax);

            $result['payment_methods'][] = $method;
        }

        // return shipping methods only if a delivery country is set and if the mapping is activated in the plugin
        $sgShippingMethods   = array();
        if (!empty($userDeliveryAddress) && strlen($userCountry) > 0) {
            $shippingMethods   = $this->getShippingMethods($cart, $userCountryId);
            $shippingMethods   = $this->cartHelper->adjustShippingCosts(
                $shippingMethods,
                $result['payment_methods'],
                $userCountryId
            );
            $sgShippingMethods = $this->convertShippingFormat($shippingMethods);
        }
        if (!empty($sgShippingMethods)) {
            $result["shipping_methods"] = $sgShippingMethods;
        }

        $basketArray = $basket->sGetBasket();
        foreach ($basketArray['content'] as $item) {
            if ($item['modus'] == 3) {
                $coupon = new ShopgateExternalCoupon();
                $coupon->setIsValid(true);
                $coupon->setIsFreeShipping(false);
                $coupon->setCode($item['ordernumber']);
                $coupon->setName($item['articlename']);
                $coupon->setAmount(abs($item['priceNumeric']));
                $coupon->setCurrency($this->system->sCurrency['currency']);

                $info                = array();
                $info["order_code"]  = $item['ordernumber'];
                $info["tax_percent"] = $basket->getMaxTax();
                $info["voucher_id"]  = 0;
                $coupon->setInternalInfo($this->jsonEncode($info));

                $result["external_coupons"][$coupon->getCode()] = $coupon;
            }
        }

        // Delete basket
        $basket->sDeleteBasket();

        return $result;
    }

    /**
     * @param array $paymentMethods
     * @param int   $paymentId
     *
     * @return mixed
     */
    private function getPaymentMethod($paymentMethods, $paymentId)
    {
        foreach ($paymentMethods as $method) {
            if ($method['id'] == $paymentId) {
                return $method;
            }
        }

        return false;
    }

    /**
     * Fetches tax in percent that should be used for payment means
     *
     * @param $basket
     *
     * @return float
     */
    protected function fetchPaymentTaxFromBasket($basket)
    {
        $taxAutoMode = $this->system->sCONFIG['sTAXAUTOMODE'];
        if (!empty($taxAutoMode)) {
            $tax = $basket->getMaxTax();
        } else {
            $tax = $this->system->sCONFIG['sDISCOUNTTAX'];
        }

        // hardcoded value from shopware core
        if (empty($tax)) {
            $tax = 19.00;
        }

        return $tax;
    }

    /**
     * Calculates net value based on provided gross and tax percent
     *
     * @param float $cost
     * @param float $tax
     *
     * @return float
     */
    protected function calculateNetPaymentCosts($cost, $tax)
    {
        if ((!Shopware()->System()->sUSERGROUPDATA['tax']
            && Shopware()->System()->sUSERGROUPDATA['id'])
        ) {
            $discountNet = $cost;
        } else {
            $discountNet = round($cost / (100 + $tax) * 100, 3);
        }

        return $discountNet;
    }

    /**
     * @param ShopgateCart $cart
     * @param sBasket      $basket
     *
     * @return array
     */
    protected function validateItems(ShopgateCart $cart, $basket)
    {
        $result = array();
        foreach ($cart->getItems() as $item) {
            $cartItem = new ShopgateCartItem();
            $cartItem->setItemNumber($item->getItemNumber());
            $cartItem->setOptions($item->getOptions());
            $cartItem->setInputs($item->getInputs());
            $cartItem->setAttributes($item->getAttributes());

            $priceNet      = $item->getUnitAmount();
            $price         = $item->getUnitAmountWithTax();
            $taxRate       = $item->getTaxPercent();
            $quantity      = $item->getQuantity();
            $info          = $this->jsonDecode($item->getInternalOrderInfo(), true);
            $purchaseSteps = false;
            if (isset($info['purchasesteps']) && $info['purchasesteps'] > 1) {
                $purchaseSteps = $info['purchasesteps'];
            }
            if (!empty($purchaseSteps)) {
                $quantity = $quantity * $purchaseSteps;
                $price    = $price / $purchaseSteps;
                $priceNet = $priceNet / $purchaseSteps;
            }

            $detailOrderNumber = $item->getItemNumberPublic();

            try {
                $insertId = $basket->sAddArticle($detailOrderNumber, $quantity);
            } catch (Exception $e) {
                $this->log("Error getting insertId in Validate Items." . $e->getMessage(), ShopgateLogger::LOGTYPE_ERROR);
            }

            $isBuyable     = false;
            $qtyBuyable    = 0;
            $stockQuantity = 0;

            if (!isset($insertId)) {
                $cartItem->setError(ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK);
            } elseif ($insertId === false) {
                $cartItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND);
            } else {
                $updateData = array(
                    'price'    => $this->formatPriceNumber($price),
                    'netprice' => $this->formatPriceNumber($priceNet),
                    'tax_rate' => $this->formatPriceNumber($taxRate),
                );
                Shopware()->Db()->update('s_order_basket', $updateData, "id={$insertId}");

                $basketItem = $this->getBasketItem($insertId);

                /* @var $mainDetail \Shopware\Models\Article\Detail */
                $mainDetail = Shopware()->Models()
                    ->getRepository("\Shopware\Models\Article\Detail")
                    ->findOneBy(array("number" => $detailOrderNumber));

                if (!empty($basketItem) && is_object($mainDetail) && $mainDetail->getActive()) {
                    $stockQuantity = $mainDetail->getInStock();

                    $isBuyable = $basketItem['quantity'] < $item->getQuantity()
                        ? false
                        : true;

                    $qtyBuyable = (int)$basketItem['quantity'];
                    $price      = $basketItem['price'];

                    if (!empty($purchaseSteps)) {
                        $qtyBuyable    /= $purchaseSteps;
                        $stockQuantity /= $purchaseSteps;
                        $price         *= $purchaseSteps;
                    }

                    if (!$isBuyable) {
                        $cartItem->setError(ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE);
                    }
                    $cartItem->setStockQuantity(floor($stockQuantity));
                    $cartItem->setUnitAmount($price);
                    $cartItem->setUnitAmountWithTax($price);
                } else {
                    $cartItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND);
                }
            }

            $cartItem->setIsBuyable($isBuyable);
            $cartItem->setQtyBuyable(floor($qtyBuyable));
            $cartItem->setStockQuantity(floor($stockQuantity));

            $result[] = $cartItem;
        }

        return $result;
    }

    /**
     *
     * @param string $code
     *
     * @return \Shopware\Models\Voucher\Voucher
     */
    protected function loadVoucher($code)
    {
        $result = array(
            "voucher" => null,
            "code"    => null,
        );

        $dql = "
			SELECT v
			FROM \Shopware\Models\Voucher\Voucher v
			WHERE v.voucherCode = :vouchercode";

        $result["voucher"] = Shopware()->Models()->createQuery($dql)
            ->setMaxResults(1)
            ->setParameter("vouchercode", $code)
            ->getOneOrNullResult();

        if (!$result["voucher"]) {
            $dql = "
				SELECT v
				FROM \Shopware\Models\Voucher\Code v
				WHERE v.code = :vouchercode";

            $result["code"] = Shopware()->Models()->createQuery($dql)
                ->setMaxResults(1)
                ->setParameter("vouchercode", $code)
                ->getOneOrNullResult();

            if ($result["code"]) {
                $result["voucher"] = $result["code"]->getVoucher();
            }
        }

        /** @var Enlight_Event_EventManager $eventManager */
        $eventManager = Shopware()->Events();
        $result       = $eventManager->filter(
            'Shopgate_LoadVoucher_FilterResult',
            $result,
            array(
                'subject' => $this,
                'code'    => $code,
            )
        );

        return $result;
    }

    /**
     * @param $voucherOrderCode
     *
     * @return mixed
     */
    protected function getBasketDiscountAmount($voucherOrderCode)
    {
        $result = Shopware()->Db()->fetchRow(
            "
            SELECT
				SUM(quantity*(floor(price * 100 + .55)/100)) AS totalAmount
			FROM s_order_basket
			WHERE sessionID=?
			AND ordernumber=?
			GROUP BY sessionID",
            array(
                $this->system->sSESSION_ID,
                $voucherOrderCode,
            )
        );

        return $result["totalAmount"];
    }

    /**
     * returns item from basket by id
     *
     * @param $insertId
     *
     * @return mixed
     */
    protected function getBasketItem($insertId)
    {
        $result = Shopware()->Db()->fetchRow(
            "
            SELECT
				*
			FROM s_order_basket
			WHERE id=?",
            $insertId
        );

        return $result;
    }

    /**
     * Loads all available shipping methods from shop based on the given cart
     *
     * @param ShopgateCart $cart
     * @param              $countryId
     *
     * @return array
     */
    protected function getShippingMethods(ShopgateCart $cart, $countryId)
    {
        $payment = $this->getShopPaymentMethod(
            $this->getShopPaymentMethodName(
                $cart->getPaymentMethod(),
                self::CHECK_CART_PAYMENT_METHOD
            )
        );

        Shopware()->System()->_SESSION['sPaymentID'] = $payment->getId();

        // let shopware handle the loading of the shipping methods
        $shippingMethods = Shopware()->Modules()->Admin()->sGetPremiumDispatches($countryId, $payment->getId());

        if (empty($shippingMethods)) {
            $shippingMethods = array();
        } else {
            // load shipping costs data
            foreach ($shippingMethods as $key => $shippingMethod) {
                // get additional data
                $shippingMethodData = Shopware()->Models()
                    ->getRepository('Shopware\Models\Dispatch\Dispatch')
                    ->getShippingCostsQuery(
                        $shippingMethod['id'],
                        null, // no filter
                        array(
                            array(
                                'property'  => 'dispatch.name',
                                'direction' => 'ASC',
                            ),
                        ) // sort ascending by name
                    )->getArrayResult();

                // append data
                $shippingMethods[$key] += $shippingMethodData[0];

                // fetch shipping costs
                Shopware()->System()->_SESSION['sDispatch'] = $key;

                $countryInfo = array(
                    'id' => $countryId,
                );
                $costs       = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts($countryInfo);

                $shippingMethods[$key]['shipping_costs'] = $costs['brutto'];

                if ($payment->getSurcharge() > 0 && $shippingMethods[$key]['surchargeCalculation'] !== 3) {
                    $shippingMethods[$key]['shipping_costs'] -= $costs['surcharge'];
                }
            }
        }
        Shopware()->Session()->sCountry = $countryId;

        return $shippingMethods;
    }

    /**
     * @param $shippingMethods
     *
     * @return array
     */
    protected function convertShippingFormat($shippingMethods)
    {
        $shopgateShippingMethods = array();
        if (!empty($shippingMethods)) {
            foreach ($shippingMethods as $key => $shippingMethod) {
                // only take active shipping methods
                if (!empty($shippingMethod['active'])) {
                    // no need to have synchronous key, so use same key as original shippingMethod
                    $shopgateShippingMethods[$key] = new ShopgateShippingMethod();
                    $shopgateShippingMethods[$key]->loadArray(
                        array(
                            'id'              => $shippingMethod['name'],
                            'title'           => $shippingMethod['name'],
                            'shipping_group'  => 'OTHER',
                            'description'     => $shippingMethod['description'],
                            'sort_order'      => $shippingMethod['position'],
                            'amount'          => $this->formatPriceNumber(
                                $shippingMethod['shipping_costs']
                                * 100 / (100
                                    + $shippingMethod['max_tax']),
                                2
                            ),
                            'amount_with_tax' => $this->formatPriceNumber(
                                $shippingMethod['shipping_costs'],
                                2
                            ),
                            'tax_class'       => null,
                            'tax_percent'     => $shippingMethod['max_tax'],

                            'internal_shipping_info' => $this->jsonEncode(
                                array(
                                    'id'                            => $shippingMethod['id'],
                                    'active'                        => $shippingMethod['active'],
                                    'multiShopId'                   => $shippingMethod['multiShopId'],
                                    'customerGroupId'               => $shippingMethod['customerGroupId'],
                                    'is_check_cart_shipping_method' => true,
                                )
                            ),
                        )
                    );
                }
            }
        }

        return $shopgateShippingMethods;
    }

    /**
     * checks stock information for specific product
     *
     * @throws ShopgateLibraryException
     * @see http://developer.shopgate.com/plugin_api/stock
     */
    public function checkStock(ShopgateCart $cart)
    {
        Shopware()->Session()->sUserId = $cart->getExternalCustomerId();
        $basket                        = Shopware()->Modules()->Basket();

        $shopgateCartItems = $this->validateItems($cart, $basket);

        // Delete basket
        $basket->sDeleteBasket();

        return $shopgateCartItems;
    }


    ############################################################################
    ## INSERT ORDER HELPER                                                    ##
    ############################################################################

    /**
     * @param $name
     * @param $options
     *
     * @return string
     */
    protected function _buildOrderArticleName($name, $options)
    {
        $returnString = $name;
        foreach ($options as $option) {
            $returnString .= ' ' . $option->getName() . ':';
            $returnString .= ' ' . $option->getValue();
        }

        return $returnString;
    }

    /**
     * Checks if the given table exists
     *
     * @param string $tableName
     *
     * @throws ShopgateLibraryException
     * @return boolean
     */
    protected function tableExists($tableName)
    {
        $tableName = trim($tableName);
        if (empty($tableName)) {
            return false;
        }

        // Get all table names
        $result = Shopware()->Db()->fetchAll("SHOW TABLES");

        foreach ($result as $row) {
            $row = array_values($row);

            // Check for table name
            if ($row[0] == $tableName) {
                return true;
            }
        }

        // The requested table has not been found if execution reaches here
        return false;
    }


    ############################################################################
    ## SETTINGS                                                               ##
    ############################################################################

    public function getSettings()
    {
        $settings = array(
            'customer_groups'            => $this->settingsComponent->getCustomerGroups(),
            'tax'                        => $this->settingsComponent->getTaxSettings(),
            'allowed_address_countries'  => $this->settingsComponent->getAllowedAddresses(),
            'allowed_shipping_countries' => $this->settingsComponent->getAllowedAddresses(),
            'payment_methods'            => $this->settingsComponent->getAllPaymentMethods(),
        );

        return $settings;
    }

    ############################################################################
    ## XML EXPORT                                                             ##
    ############################################################################

    /**
     * export items as xml
     *
     * @param null  $limit
     * @param null  $offset
     * @param array $uids
     */
    protected function createItems($limit = null, $offset = null, array $uids = array())
    {
        $this->log("[*] Export Start...", ShopgateLogger::LOGTYPE_DEBUG);
        $start                = microtime(1);
        $memoryUsageBegin     = memory_get_usage();
        $memoryUsageRealBegin = memory_get_usage(true);

        $this->log(
            "ShopgatePluginShopware::createItems() memory usage at beginning: " . $this->getMemoryUsageString(),
            ShopgateLogger::LOGTYPE_DEBUG
        );
        $this->log("ShopgatePluginShopware::createItems() loading article export ids", ShopgateLogger::LOGTYPE_DEBUG);

        $articles = $this->getExportArticles($limit, $offset, $uids);

        $this->log(
            'ShopgatePluginShopware::createItems() found ' . count($articles) . 'articles',
            ShopgateLogger::LOGTYPE_DEBUG
        );

        foreach ($articles as $data) {
            try {
                $exportModel = $this->buildItem($data);
            } catch (Exception $e) {
                $msg = "Product with id #{$data['id']} cannot export!\n";
                $msg .= "Exception: " . get_class($e) . "\n";
                $msg .= "Message: " . $e->getMessage() . "\n";
                $msg .= "Trace:\n" . $e->getTraceAsString();
                $this->log($msg);
                $exportModel = 0;
            }
            if (!empty($exportModel)) {
                $this->addItem($exportModel->generateData());
            }
        }

        // output memory footprint to debug log
        $memoryUsageDiff     = memory_get_usage() - $memoryUsageBegin;
        $memoryUsageRealDiff = memory_get_usage(true) - $memoryUsageRealBegin;
        $memUsageDiffString  = $this->getFormatedMemoryDiffString($memoryUsageDiff, $memoryUsageRealDiff);
        $end                 = microtime(1);
        $duration            = $end - $start;
        $this->log(
            "ShopgatePluginShopware::createItems() memory usage DIFFERENCE after creating the XML file: "
            . $memUsageDiffString,
            ShopgateLogger::LOGTYPE_DEBUG
        );
        $this->log("[*] Export duration {$duration} seconds", ShopgateLogger::LOGTYPE_DEBUG);
        $this->log("[*] Export End...", ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * @param int      $limit
     * @param int      $offset
     * @param string[] $uids
     *
     * @return array
     */
    public function getExportArticles($limit = null, $offset = null, array $uids = array())
    {
        $builder = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')
            ->createQueryBuilder('article')
            ->leftJoin('article.categories', 'categories', null, null, 'categories.id')
            ->leftJoin('article.mainDetail', 'mainDetail', null, null, 'mainDetail.id');

        if ($offset !== null && $limit !== null) {
            $builder->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $builder->where('article.active = 1');

        // Currently we can only export stacking products which have minPurchase quantity to be a multiple of the purchaseSteps
        $builder->andWhere(
            '(mainDetail.purchaseSteps IS NULL OR mainDetail.purchaseSteps IN (0, 1) OR mainDetail.purchaseSteps IS NOT NULL AND MOD(mainDetail.minPurchase , mainDetail.purchaseSteps)  = 0 )'
        );

        if (!empty($uids) && is_array($uids)) {
            $builder->andWhere($builder->expr()->in('article.id', ':uids'))
                ->setParameter('uids', $uids);
        } else {
            $storeCategories = $this->exportComponent->getLanguageCompleteCategoryList();
            if ($storeCategories) {
                $builder->andWhere($builder->expr()->in('categories.id', $storeCategories));
            }

            $skipIds = $this->config->getExcludeItemIds();
            if (!empty($skipIds)) {
                $builder->andWhere($builder->expr()->notIn('article.id', ':skipids'))->setParameter(
                    'skipids',
                    $skipIds
                );
            }
        }
        $builder->addGroupBy('article.id');

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * @param $data
     *
     * @return null|Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product_Xml
     */
    public function buildItem($data)
    {
        $this->log(
            __CLASS__ . '::' . __FUNCTION__ . " memory usage BEFORE product export [articleId={$data['id']}]: "
            . $this->getMemoryUsageString(),
            ShopgateLogger::LOGTYPE_DEBUG
        );

        if (empty($data['mainDetailId'])) {
            $this->log(
                __CLASS__ . '::' . __FUNCTION__ . " mainDetailId missing [articleId={$data['id']}]",
                ShopgateLogger::LOGTYPE_DEBUG
            );

            return null;
        }

        // reset post data
        Shopware()->System()->_POST['group'] = array();

        /* @var $mainDetail \Shopware\Models\Article\Detail */
        $mainDetail = Shopware()->Models()->find('\Shopware\Models\Article\Detail', $data['mainDetailId']);

        if (!is_object($mainDetail)) {
            $this->log(
                __CLASS__ . '::' . __FUNCTION__ . " mainDetail missing [articleId={$data['id']}]",
                ShopgateLogger::LOGTYPE_DEBUG
            );

            return null;
        }

        /* @var $article Shopware\Models\Article\Article */
        $article = $mainDetail->getArticle();

        $valid = false;
        foreach ($article->getCategories()->getIterator() as $category) {
            /* @var $category \Shopware\Models\Category\Category */
            if ($this->exportComponent->checkCompleteCategory($category->getId())) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $this->log(
                __CLASS__ . '::' . __FUNCTION__ . " category invalid [articleId={$data['id']}]",
                ShopgateLogger::LOGTYPE_DEBUG
            );

            return null;
        }

        $exportModel = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product_Xml(
            $this->exportComponent,
            $this->translationModel
        );
        $exportModel->setDefaultCustomerGroupKey($this->defaultCustomerGroupKey);
        $exportModel->setConfig($this->config);
        $exportModel->setDetail($mainDetail);
        $exportModel->setArticle($article);

        /** @var sArticles $sArticlesObject */
        $sArticlesObject             = Shopware()->Modules()->Articles();
        $sArticlesObject->category   = $category;
        $sArticlesObject->categoryId = $category->getId();
        $sArticlesObject->translationId = $this->locale->getId();

        $articleData = $sArticlesObject->sGetArticleById($article->getId());
        if ((empty($articleData) || !isset($articleData['price']))
            && is_object($category->getParent())
        ) {
            $sArticlesObject->category   = $category->getParent();
            $sArticlesObject->categoryId = $category->getParent()->getId();
            $articleData                 = $sArticlesObject->sGetArticleById($article->getId());
        }

        $exportModel->setArticleData($articleData);
        $exportModel->setIsChild(false);

        $unit = $mainDetail->getUnit();
        if ($unit) {
            /** Apply translations for unit data */
            $unitData = $sArticlesObject->sGetUnit($unit->getId());
            if (!empty($unitData['unit'])) {
                $unit->setUnit($unitData['unit']);
            }
            if (!empty($unitData['description'])) {
                $unit->setName($unitData['description']);
            }
        }

        return $exportModel;
    }

    protected function createCategories($limit = null, $offset = null, array $uids = array())
    {
        $this->log("ShopgatePluginShopware::createCategories", ShopgateLogger::LOGTYPE_DEBUG);
        $this->categoryComponent = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Category();

        $dql = "SELECT c FROM \Shopware\Models\Category\Category c WHERE c.id = :root_category";
        /* @var $rootCategory \Shopware\Models\Category\Category */
        $rootCategory =
            Shopware()->Models()->createQuery($dql)->setParameter("root_category", $this->config->getRootCategory())
                ->getOneOrNullResult();
        $this->setMaxCategoryPosition($rootCategory);

        // get only categories which are sub categories of the root category
        $subCategories    = $this->categoryComponent->getCategories(array(), $rootCategory->getId());
        $subCategoriesIds = array_keys($subCategories);
        $repo             = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        $builder          = $repo->createQueryBuilder('c');
        $builder->andWhere('c.blog = 0');
        if (empty($subCategoriesIds)) {
            return array();
        } else {
            $subCategoriesIds = implode(',', $subCategoriesIds);
            $builder->andWhere('c.id IN (' . $subCategoriesIds . ')');
        }
        if (!empty($uids)) {
            $uids = implode(',', $uids);
            $builder->andWhere('c.id IN (' . $uids . ')');
        }
        if (isset($limit)) {
            $builder->setMaxResults($limit);
            if (isset($offset)) {
                $builder->setFirstResult($offset);
            }
        }
        $query      = $builder->getQuery();
        $categories = $query->getResult();
        $sCategoryObject = Shopware()->Modules()->Categories();

        /* @var $category \Shopware\Models\Category\Category */
        foreach ($categories as $category) {
            if (!$this->exportComponent->checkCategory($category->getId())
                || $category->getId() == $rootCategory->getId()
            ) {
                // export only categories that are associated to the current shop.
                // The root category shouldn't be exported
                continue;
            }
            $categoryContent = $sCategoryObject->sGetCategoryContent($category->getId());
            $categoryExportModel = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Category_Xml();
            $categoryExportModel->setItem($category);
            $categoryExportModel->setCategoryContent($categoryContent);
            $categoryExportModel->setMaximumPosition($this->iMaxCategoryPosition);
            $categoryExportModel->setRootCategoryId($rootCategory->getId());
            $this->addCategoryModel($categoryExportModel->generateData());
        }
    }

    /**
     * export reviews as xml
     *
     * @param null  $limit
     * @param null  $offset
     * @param array $uids
     */
    protected function createReviews($limit = null, $offset = null, array $uids = array())
    {
        $qry = $this->getReviewExportQuery($offset, $limit, $uids);
        /** @var \Shopware\Models\Article\Vote $review */
        foreach ($qry->getResult() as $review) {
            try {
                $reviewExportModel = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Review_Xml();
                $reviewExportModel->setItem($review);
                $this->addReviewModel($reviewExportModel->generateData());
            } catch (Exception $e) {
                $this->log(
                    'Exception in export of review id ' . $review->getId() . 'for article id  '
                    . $review->getArticle()->getId() . 'with message ' . $e->getMessage()
                );
            }
        }
    }

    ############################################################################
    ## Shopinfos                                                              ##
    ############################################################################

    /**
     * get additional data from the shopware instance
     *
     * @return array|mixed[]
     */
    public function createShopInfo()
    {
        $shopInfo          = parent::createShopInfo();
        $rootCatId         = $this->shop->getCategory()->getId();
        $installer         = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Install();
        $categoryComponent = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Category();
        $catIds            = $categoryComponent->getCategories(array(), $rootCatId);

        $itemCount = $installer->getItems($catIds);

        $sql         = "
			SELECT count(id)
			FROM s_articles_vote
			WHERE active = 1
		";
        $reviewCount = Shopware()->Db()->fetchOne($sql);

        $entitiesCount = array(
            'category_count' => count($catIds),
            'item_count'     => (int)$itemCount,
            'review_count'   => (int)$reviewCount,
        );

        $pluginQry = "
			SELECT `name`, version, active
			FROM s_core_plugins
			WHERE source != 'Default' AND name != 'SgateShopgatePlugin'
		";

        $plugins = array();
        foreach (Shopware()->Db()->fetchAll($pluginQry) as $pluginData) {
            $plugins[] = array(
                'name'      => $pluginData['name'],
                'id'        => $pluginData['name'],
                'version'   => (string)$pluginData['version'],
                'is_active' => (string)$pluginData['active'],
            );
        }

        $pluginsInstalled = array(
            'plugins_installed' => $plugins,
        );

        return array_merge($shopInfo, $entitiesCount, $pluginsInstalled);
    }

    /**
     * Exports orders from the shop system's database to Shopgate.
     *
     * @see http://developer.shopgate.com/plugin_api/orders/get_orders
     *
     * @param string $customerToken
     * @param string $customerLanguage
     * @param int    $limit
     * @param int    $offset
     * @param string $orderDateFrom
     * @param string $sortOrder
     *
     * @return ShopgateExternalOrder[] A list of ShopgateExternalOrder objects
     *
     * @throws ShopgateLibraryException
     */
    public function getOrders(
        $customerToken,
        $customerLanguage,
        $limit = 10,
        $offset = 0,
        $orderDateFrom = '',
        $sortOrder = 'created_desc'
    ) {

        /* @var $shopgateCustomer \Shopware\CustomModels\Shopgate\Customer */
        $shopgateCustomer = Shopware()->Models()
            ->getRepository('\Shopware\CustomModels\Shopgate\Customer')
            ->findOneBy(array('token' => $customerToken));

        $orderComponent = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Order();

        if ($shopgateCustomer) {
            $customerId = $shopgateCustomer->getCustomerId();
            $response   = array();

            switch ($sortOrder) {
                case 'created_asc':
                    $orderBy = array(
                        'property'  => 'o.orderTime',
                        'direction' => 'ASC',
                    );
                    break;
                case 'created_desc':
                    $orderBy = array(
                        'property'  => 'o.orderTime',
                        'direction' => 'DESC',
                    );
                    break;
                default:
                    $orderBy = null;
            }

            $builder = Shopware()->Models()->createQueryBuilder();
            $builder->select(array('o'))
                ->from('Shopware\Models\Order\Order', 'o')
                ->where(Shopware()->Db()->quoteInto('o.customer = ?', $customerId))
                ->andWhere($builder->expr()->notIn('o.status', array('-1', '4')))
                ->setFirstResult($offset)
                ->setMaxResults($limit);

            if (!empty($orderDateFrom)) {
                $builder->andWhere(Shopware()->Db()->quoteInto('o.orderTime >= ?', $orderDateFrom));
            }

            if ($orderBy !== null
                && !empty($orderBy['property'])
                && !empty($orderBy['direction'])
            ) {
                $builder->addOrderBy(
                    array(
                        $orderBy,
                    )
                );
            }

            $shopwareCustomer = $shopgateCustomer->getCustomer();

            foreach ($builder->getQuery()->getResult() as $order) {
                /* @var $order Shopware\Models\Order\Order */
                /* @var $sgOrder \Shopware\CustomModels\Shopgate\Order */
                $sgOrder = Shopware()->Models()
                    ->getRepository('\Shopware\CustomModels\Shopgate\Order')
                    ->findOneBy(array('orderId' => $order->getId()));

                $shopgateOrder = $sgOrder
                    ? $sgOrder->getReceivedData()
                    : null;

                $shopgateExternalOrder = new ShopgateExternalOrder();
                $shopgateExternalOrder->setOrderNumber(
                    ($shopgateOrder)
                        ? $shopgateOrder->getOrderNumber()
                        : null
                );
                $shopgateExternalOrder->setExternalOrderId($order->getId());
                $shopgateExternalOrder->setExternalOrderNumber($order->getNumber());
                $shopgateExternalOrder->setCreatedTime($order->getOrderTime()->format(DateTime::ISO8601));
                $shopgateExternalOrder->setMail($shopwareCustomer->getEmail());
                $shopgateExternalOrder->setCurrency($order->getCurrency());
                try {
                    $shopgateExternalOrder->setPaymentMethod($order->getPayment()->getDescription());
                } catch (Exception $e) {
                    $this->log($e->getMessage());
                }
                $shopgateExternalOrder->setIsPaid(
                    ($shopgateOrder)
                        ? $shopgateOrder->getIsPaid()
                        : null
                );
                $shopgateExternalOrder->setPaymentTransactionNumber(
                    ($shopgateOrder)
                        ? $shopgateOrder->getPaymentTransactionNumber()
                        : $order->getTransactionId()
                );
                $shopgateExternalOrder->setAmountComplete($order->getInvoiceAmount());
                $shopgateExternalOrder->setItems($orderComponent->getOrderItemsFormatted($order));
                $shopgateExternalOrder->setOrderTaxes($orderComponent->getOrderTaxFormatted($order));
                $shopgateExternalOrder->setDeliveryNotes($orderComponent->getDeliveryNotes($order));
                $shopgateExternalOrder->setExternalCoupons($orderComponent->getCouponsFormatted($order));

                if ($order->getBilling()) {
                    $shopgateExternalOrder->setInvoiceAddress(
                        $orderComponent->getShopgateAddressFromOrderAddress(
                            $order->getBilling(),
                            $order->getShop()
                        )
                    );
                }
                if ($order->getShipping()) {
                    $shopgateExternalOrder->setDeliveryAddress(
                        $orderComponent->getShopgateAddressFromOrderAddress(
                            $order->getShipping(),
                            $order->getShop()
                        )
                    );
                }

                array_push($response, $shopgateExternalOrder);
            }

            return $response;
        } else {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CUSTOMER_TOKEN_INVALID);
        }
    }

    /**
     * Updates and returns synchronization information for the favourite list of a customer.
     *
     * @see http://developer.shopgate.com/plugin_api/customers/sync_favourite_list
     *
     * @param string             $customerToken
     * @param ShopgateSyncItem[] $items A list of ShopgateSyncItem objects that need to be synchronized
     *
     * @return ShopgateSyncItem[] The updated list of ShopgateSyncItem objects
     */
    public function syncFavouriteList($customerToken, $items)
    {
        // TODO: Implement syncFavouriteList() method.
    }
}
