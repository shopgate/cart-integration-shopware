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
use \Firebase\JWT\JWT;
use SwagAdvancedCart\Models\Cart\Cart;

class Shopware_Controllers_Frontend_Shopgate extends Enlight_Controller_Action implements CSRFWhitelistAware
{

    /**
     * Reference to sBasket object (core/class/sBasket.php)
     *
     * @var sBasket
     */
    protected $basket;

    /**
     * @var sAdmin
     */
    protected $admin;

    /**
     * Database connection which used for each database operation in this class.
     * Injected over the class constructor
     *
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * Reference to Shopware session object (Shopware()->Session)
     *
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

    /**
     * Check if current active shop has own registration
     *
     * @var bool s_core_shops.customer_scope
     */
    public $scopedRegistration;

    /**
     * Id of current active shop
     *
     * @var int s_core_shops.id
     */
    public $subshopId;

    /**
     * Shopware password encoder.
     * Injected over the class constructor
     *
     * @var \Shopware\Components\Password\Manager
     */
    private $passwordEncoder;

    /**
     * @var StoreFrontBundle\Service\ProductServiceInterface
     */
    private $productService;

    /**
     * @var StoreFrontBundle\Service\ContextServiceInterface
     */
    private $contextService;

    /**
     * Init method that get called automatically
     *
     * Set class properties
     */
    public function init()
    {
        $container = Shopware()->Container();

        $this->basket = Shopware()->Modules()->Basket();
        $this->admin = Shopware()->Modules()->Admin();
        $this->session = Shopware()->Session();
        $this->db = Shopware()->Db();
        $this->passwordEncoder = Shopware()->PasswordEncoder();

        $this->contextService = $container->get('shopware_storefront.context_service');
        $this->productService = $container->get('shopware_storefront.product_service');

        $this->subshopId = $this->contextService->getShopContext()->getShop()->getParentId();

        $mainShop = Shopware()->Shop()->getMain() !== null ? Shopware()->Shop()->getMain() : Shopware()->Shop();
        $this->scopedRegistration = $mainShop->getCustomerScope();
    }

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
            'loginUser',
            'addToCart',
            'deleteCartItem',
            'updateCartItem',
            'addCouponsCode',
            'account',
            'accountOrders',
            'updateUserPassword',
            'updateUserEmail',
            'updateUser',
            'addToFavoriteList',
            'deleteFromFavoriteList',
            'addAddress',
            'deleteAddress',
            'updateAddress'
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

    /**
     * Custom function for password reset
     */
    public function passwordAction()
    {
        $this->session->offsetSet('sgWebView', true);
        $this->redirect('account/password');
    }

    /**
     * Custom function to get the cart
     */
    public function getCartAction()
    {
        $sessionId = $this->Request()->getCookie('sg_session');
        $promotionVouchers = json_decode($this->Request()->getCookie('sg_promotion'), true);

        $this->session->offsetSet('sessionId', $sessionId);
        session_id($sessionId);

        if (isset($promotionVouchers)) {
            $this->session->offsetSet('promotionVouchers', $promotionVouchers);
        }

        $basket = $this->basket->sGetBasket();

        if (!isset($basket)) {
            $this->Response()->setHttpResponseCode(401);
            $this->Response()->setHeader('Content-Type', 'application/json');
            $this->Response()->setBody(json_encode($basket));
            $this->Response()->sendResponse();
            exit();
        }

        $shippingcosts = $this->getShippingCosts();

        $currency = $this->get('shop')->getCurrency();

        // Below code comes from the getBasket function in the Checkout Controller
        $basket['sCurrencyId'] = $currency->getId();
        $basket['sCurrencyName'] = $currency->getCurrency();
        $basket['sCurrencyFactor'] = $currency->getFactor();
        $basket['sCurrencySymbol'] = $currency->getSymbol();

        $basket['sShippingcostsWithTax'] = $shippingcosts['brutto'];
        $basket['sShippingcostsNet'] = $shippingcosts['netto'];
        $basket['sShippingcostsTax'] = $shippingcosts['tax'];

        if (!empty($shippingcosts['brutto'])) {
            $basket['AmountNetNumeric'] += $shippingcosts['netto'];
            $basket['AmountNumeric'] += $shippingcosts['brutto'];
            $basket['sShippingcostsDifference'] = $shippingcosts['difference']['float'];
        }
        if (!empty($basket['AmountWithTaxNumeric'])) {
            $basket['AmountWithTaxNumeric'] += $shippingcosts['brutto'];
        }
        if (!Shopware()->Modules()->System()->sUSERGROUPDATA['tax'] && Shopware()->Modules()->System()->sUSERGROUPDATA['id']) {
            $basket['sTaxRates'] = $this->getTaxRates($basket);

            $basket['sShippingcosts'] = $shippingcosts['netto'];
            $basket['sAmount'] = round($basket['AmountNetNumeric'], 2);
            $basket['sAmountTax'] = round($basket['AmountWithTaxNumeric'] - $basket['AmountNetNumeric'], 2);
            $basket['sAmountWithTax'] = round($basket['AmountWithTaxNumeric'], 2);
        } else {
            $basket['sTaxRates'] = $this->getTaxRates($basket);

            $basket['sShippingcosts'] = $shippingcosts['brutto'];
            $basket['sAmount'] = $basket['AmountNumeric'];

            $basket['sAmountTax'] = round($basket['AmountNumeric'] - $basket['AmountNetNumeric'], 2);
        }

        $this->Response()->setHttpResponseCode(200);
        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($basket));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom function to login user and redirect to account view
     */
    public function accountAction()
    {
        $sessionId = $this->Request()->getParam('sessionId');

        if (isset($sessionId)) {
            session_write_close();
            session_id($sessionId);
            session_start(array(
                'sessionId' => $sessionId
            ));
        }

        $token = $this->Request()->getParam('token');
        $this->loginAppUser($token);

        $this->session->offsetSet('sgWebView', true);
        $this->session->offsetSet('sgAccountView', true);

        $this->redirect('account');
    }

    /**
     * Custom function to login user and redirect to orders view
     */
    public function accountOrdersAction()
    {
        $sessionId = $this->Request()->getParam('sessionId');

        if (isset($sessionId)) {
            session_write_close();
            session_id($sessionId);
            session_start(array(
                'sessionId' => $sessionId
            ));
        }

        $token = $this->Request()->getParam('token');
        $this->loginAppUser($token);

        $this->session->offsetSet('sgWebView', true);
        $this->session->offsetSet('sgAccountView', true);

        $this->redirect('account/orders');
    }

    /**
     * Custom action to login user and redirect to paypal express
     */
    public function payPalExpressAction()
    {
        $sessionId = $this->Request()->getParam('sessionId');

        if (isset($sessionId)) {
            session_write_close();
            session_id($sessionId);
            session_start(array(
                'sessionId' => $sessionId
            ));
        }

        $token = $this->Request()->getParam('token');
        $this->loginAppUser($token);

        $this->session->offsetSet('sgWebView', true);

        $this->redirect('checkout/cart');
    }

    /**
     * Custom action to login user and redirect to checkout
     */
    public function checkoutAction()
    {
        $sessionId = $this->Request()->getParam('sessionId');

        if (isset($sessionId)) {
            session_write_close();
            session_id($sessionId);
            session_start(array(
                'sessionId' => $sessionId
            ));
        }

        $token = $this->Request()->getParam('token');
        $this->loginAppUser($token);

        $this->session->offsetSet('sgWebView', true);

        $this->redirect('checkout/confirm');
    }

    /**
     * @throws Exception
     */
    public function addToCartAction()
    {
        if (!$this->Request()->isPost()) {
            return;
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->getJsonParams();

        $articles = $params['articles'];
        $sessionId = $params['sessionId'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);

        if (!isset($articles)) {
            $this->Response()->setHttpResponseCode(401);
            $this->Response()->setBody(json_encode(array('message' => 'The request doesn\'t contain an \'articles\' parameter!')));
        }

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if (isset($promotionVouchers)) {
            $this->session->offsetSet('promotionVouchers', $promotionVouchers);
        }

        $response = $this->addArticlesToCart($articles, $sessionId);
        if ($response) {
            $this->Response()->setHttpResponseCode(401);
            $this->Response()->setBody(json_encode($response));
        } else {
            $sessionId = $this->session->get('sessionId');
            $this->Response()->setHttpResponseCode(201);
            $this->Response()->setBody(json_encode(array('sessionId'=> $sessionId)));
        }
        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom function to add coupon to cart
     */
    public function addCouponsCodeAction()
    {
        if (!$this->Request()->isPost()) {
            return;
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->getJsonParams();

        $code = $params['couponCode'];
        $sessionId = $params['sessionId'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);
        $customerId = $params['customerId'];

        if (isset($customerId)) {
            $sql = 'SELECT DISTINCT `id` FROM `s_user` WHERE customernumber=?';
            $userId = Shopware()->Db()->fetchCol($sql, array($customerId));
            $this->session->offsetSet('sUserId', $userId[0]);
        }

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if (isset($promotionVouchers)) {
            $this->session->offsetSet('promotionVouchers', $promotionVouchers);
        }

        $this->Response()->setHeader('Content-Type', 'application/json');

        $voucher = $this->basket->sAddVoucher($code);
        $response['addVoucher'] = $voucher;

        if ($voucher) {
            $response['promotionVouchers'] = json_encode($this->session->get('promotionVouchers'));

            $this->Response()->setBody(html_entity_decode(json_encode($response)));
            $this->Response()->sendResponse();
            exit();
        }

        $this->basket->sGetBasket();

        $promotionVariables = $this->View()->getAssign();
        $voucherPromotionId = $promotionVariables['voucherPromotionId'];
        $promotionUsedTooOften = $promotionVariables['promotionsUsedTooOften'];
        $promotions = $this->session->get('promotionVouchers');

        foreach ($promotionUsedTooOften as $promotionUsed) {
            if ($promotionUsed->id == $voucherPromotionId) {
                $response['addVoucher']['sErrorFlag'] = true;
                $text = Shopware()->Snippets()->getNamespace('frontend/swag_promotion/main')->get('usedPromotions');
                $text = str_replace('{$promotionUsedTooOften->name}', $promotionUsed->name, $text);
                $text = str_replace('{$promotionUsedTooOften->maxUsage}', $promotionUsed->maxUsage, $text);
                $response['addVoucher']['sErrorMessages'][0] = $text;

                foreach ($promotions as $key => $promotion) {
                    if ($promotion['promotionId'] == $voucherPromotionId) {
                        unset($promotions[$key]);
                    }
                }
            }
        }

        $response['promotionVouchers'] = json_encode($promotions);

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(html_entity_decode(json_encode($response)));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom function to delete article from cart
     */
    public function deleteCartItemAction()
    {
        if (!$this->Request()->isDelete()) {
            return;
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->getJsonParams();

        $articleId = $params['articleId'];
        $sessionId = $params['sessionId'];
        $voucher = $params['voucher'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);

        $response['oldPromotionVouchers'] = $promotionVouchers;

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if (isset($promotionVouchers) && $voucher) {
            $sql = 'SELECT DISTINCT `ordernumber` FROM `s_order_basket` WHERE id=?';
            $orderNumber = Shopware()->Db()->fetchCol($sql, array($articleId));

            $sql = 'SELECT 1 FROM `s_plugin_promotion` LIMIT 1';
            $test = Shopware()->Db()->fetchCol($sql);
            if ($test) {
                $sql = 'SELECT DISTINCT `id` FROM `s_plugin_promotion` WHERE number=?';
                $voucherId = Shopware()->Db()->fetchCol($sql, $orderNumber);

                $response['voucherId'] = $voucherId;

                if ($voucherId) {
                    $this->session->offsetSet('promotionVouchers', $promotionVouchers[$voucherId]);
                    $response['newPromotionVouchers'] = $promotionVouchers[$voucherId];
                }
            }
        }

        $response['deleteArticle'] = $this->basket->sDeleteArticle($articleId);
        $response['promotionVouchers'] = json_encode($this->session->get('promotionVouchers'));

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($response));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom function to update a cart item
     */
    public function updateCartItemAction()
    {
        if (!$this->Request()->isPut()) {
            return;
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->getJsonParams();

        $basketId = $params['basketId'];
        $quantity = $params['quantity'];
        $sessionId = $params['sessionId'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if (isset($promotionVouchers)) {
            $this->session->offsetSet('promotionVouchers', $promotionVouchers);
        }

        $this->Response()->setHeader('Content-Type', 'application/json');
        if ($error = $this->verifyItemStock($basketId, $quantity)) {
            $this->Response()->setHttpResponseCode(401);
            $this->Response()->setBody(json_encode(array(
                'error' => true,
                'reason' => $error
            )));
        } else {
            $response = $this->basket->sUpdateArticle($basketId, $quantity);
            $this->Response()->setBody(json_encode($response));
        }

        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Login app user from JWT token
     *
     * @param $token
     */
    protected function loginAppUser($token)
    {
        $basket = $this->basket->sGetBasket();
        $voucher = $this->getVoucher();

        if (isset($token)) {
            $key = trim($this->getConfig()->getApikey());
            JWT::$leeway = 60;
            $decoded = JWT::decode($token, $key, array('HS256'));
            $decoded = json_decode(json_encode($decoded), true);
            $customerId = $decoded['customer_id'];
            $promotionVouchers = json_decode($decoded['promotion_vouchers'], true);

            if (isset($promotionVouchers)) {
                $this->session->offsetSet('promotionVouchers', $promotionVouchers);
            }

            $sql = 'SELECT DISTINCT `password` FROM `s_user` WHERE customernumber=?';
            $password = Shopware()->Db()->fetchCol($sql, array($customerId));

            $sql = 'SELECT DISTINCT `email` FROM `s_user` WHERE customernumber=?';
            $email = Shopware()->Db()->fetchCol($sql, array($customerId));

            $this->Request()->setPost('email', $email[0]);
            $this->Request()->setPost('passwordMD5', $password[0]);

            $checkUser = $this->admin->sLogin(true);

            if (isset($checkUser['sErrorFlag'])) {
                throw new Exception($checkUser['sErrorMessages'][0] , 400);
            }

            $this->basket->sRefreshBasket();
            $this->basket->clearBasket();

            if (!empty($basket['content'])) {

                foreach ($basket['content'] as $basketItem) {
                    $this->basket->sAddArticle($basketItem['ordernumber'], $basketItem['quantity']);
                }

                if (!empty($voucher)) {
                    $this->basket->sAddVoucher($voucher['code']);
                }
            }
        }
    }

    /**
     * Returns the current basket voucher or false
     *
     * @return array|false
     */
    protected function getVoucher()
    {
        $voucher = $this->db->fetchRow(
            'SELECT id basketID, ordernumber, articleID as voucherID
                FROM s_order_basket
                WHERE modus = 2 AND sessionID = ?',
            array($this->session->get('sessionId'))
        );
        if (!empty($voucher)) {
            $voucher['code'] = $this->db->fetchOne(
                'SELECT vouchercode FROM s_emarketing_vouchers WHERE ordercode = ?',
                array($voucher['ordernumber'])
            );
            if (empty($voucher['code'])) {
                $voucher['code'] = $this->db->fetchOne(
                    'SELECT code FROM s_emarketing_voucher_codes WHERE id = ?',
                    array($voucher['voucherID'])
                );
            }
        }
        return $voucher;
    }

    /**
     * Verify if item quantity is in stock
     *
     * @param $basketId
     * @param $quantity
     * @return null|string
     */
    protected function verifyItemStock($basketId, $quantity)
    {
        $basket = $this->basket->sGetBasket();
        foreach ($basket['content'] as $basketItem) {
            if ($basketItem['id'] === $basketId) {
                return $this->getInstockInfo($basketItem['ordernumber'], $quantity);
            }
        }
        return null;
    }

    /**
     * @return mixed
     */
    protected function getJsonParams()
    {
        $header = $this->Request()->getHeader('Content-Type');

        if ($header !== 'application/json') {
            $this->Response()->setHttpResponseCode(404);
            $this->Response()->sendResponse();
            exit();
        }

        $content = trim(file_get_contents("php://input"));
        return json_decode($content, true);
    }

    /**
     * Adds an array of articles to the cart based on an array of article IDs
     *
     * @param $articles
     * @param $sessionId
     * @return array
     */
    protected function addArticlesToCart($articles, $sessionId)
    {
        $response = array(); // Contains only errors

        foreach ($articles as $article) {
            $articleId = trim($article['product_id']);
            $orderNumber = trim($article['variant_id']);

            $product = Shopware()->Modules()->Articles()->sGetArticleById($articleId);

            if ($product) {
                if ($orderNumber === "") {
                    $orderNumber = $product['ordernumber'];
                }

                $builder = Shopware()->Models()->getConnection()->createQueryBuilder();

                $builder->select('id', 'quantity')
                    ->from('s_order_basket', 'basket')
                    ->where('articleID = :articleId')
                    ->andWhere('sessionID = :sessionId')
                    ->andWhere('ordernumber = :ordernumber')
                    ->andWhere('modus != 1')
                    ->setParameter('articleId', $product['articleID'])
                    ->setParameter('sessionId', $sessionId)
                    ->setParameter('ordernumber', $orderNumber);

                $statement = $builder->execute();

                $quantity =  $article['quantity'];

                if ($basketProduct = $statement->fetch()) {
                    $quantity +=  $basketProduct['quantity'];
                }

                if ($infoMessage = $this->getInstockInfo($orderNumber, $quantity)) {
                    $response[$articleId] = $infoMessage;
                } else {
                    $this->basket->sAddArticle($orderNumber, $article['quantity']);
                }
            } else {
                // Fallback error message isn't translated.
                $response[$articleId] = 'Could not find article with id in cart!';
            }
        }

        return $response;
    }

    /**
     * An AJAX Request to log the user in using Shopware's internal login
     * function.
     *
     * @throws LogicException
     */
    public function loginUserAction()
    {
        if (strtolower($this->Request()->getMethod()) !== 'post') {
            throw new \LogicException('This action only admits post requests');
        }
        $hash = $this->Request()->getPost('passwordMD5');
        $sessionId = $this->Request()->getPost('sessionId');

        if (isset($sessionId)) {
            //Set session id using both methods because standard shopware login merges basket with session_id
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        $basket = $this->basket->sGetBasket();

        $this->Response()->setHeader('Content-Type', 'application/json');

        if (isset($hash)) {
            $email = strtolower($this->Request()->getPost('email'));
            $user = $this->verifyUser($email, $hash);
            if (!empty($user['sErrorMessages'])) {
                $this->Response()->setHttpResponseCode(401);
                $this->Response()->setBody(json_encode($user));
                $this->Response()->sendResponse();
                exit();
            } else {
                $this->Response()->setHttpResponseCode(200);
                $this->Response()->setBody(json_encode(array(
                    'id' => $user['customernumber'],
                    'mail' => $user['email'],
                    'first_name' => $user['firstname'],
                    'last_name' => $user['lastname'],
                    'birthday' => $user['birthday'],
                    'customer_groups' => $user['customergroup'],
                    'session_id' => $user['sessionID']
                )));
                $this->Response()->sendResponse();
                exit();
            }
        } else {
            $error = $this->admin->sLogin();
        }

        if (!empty($error['sErrorMessages'])) {
            $this->Response()->setHttpResponseCode(401);
            $this->Response()->setBody(json_encode($error));
        } else {
            if (!empty($basket['content'])) {
                $this->basket->clearBasket();
                $this->basket->sRefreshBasket();

                foreach ($basket['content'] as $basketItem) {
                    $this->basket->sAddArticle($basketItem['ordernumber'], $basketItem['quantity']);
                }
            }

            $user = $this->admin->sGetUserData();
            $user = $user['additional']['user'];

            $this->Response()->setHttpResponseCode(200);
            $this->Response()->setBody(json_encode(array(
                'id' => $user['customernumber'],
                'mail' => $user['email'],
                'first_name' => $user['firstname'],
                'last_name' => $user['lastname'],
                'birthday' => $user['birthday'],
                'customer_groups' => $user['customergroup'],
                'session_id' => $user['sessionID']
            )));
        }

        $this->basket->sRefreshBasket();

        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom get user action
     */
    public function getUserAction()
    {
        try {
            $decoded = $this->getJWT($this->Request()->getCookie('token'));
            $customerId = $decoded['customer_id'];

            $sql = 'SELECT DISTINCT `password` FROM `s_user` WHERE customernumber=?';
            $password = Shopware()->Db()->fetchCol($sql, array($customerId));

            $sql = 'SELECT DISTINCT `email` FROM `s_user` WHERE customernumber=?';
            $email = Shopware()->Db()->fetchCol($sql, array($customerId));

            $this->Request()->setPost('email', $email[0]);
            $this->Request()->setPost('passwordMD5', $password[0]);

            $checkUser = $this->admin->sLogin(true);

            if (isset($checkUser['sErrorFlag'])) {
                throw new Exception($checkUser['sErrorMessages'][0], 400);
            }

            $this->basket->sRefreshBasket();

            $user = $this->admin->sGetUserData();
            $user = $user['additional']['user'];

            $this->Response()->setBody(json_encode(array(
                'id' => $user['customernumber'],
                'mail' => $user['email'],
                'firstName' => $user['firstname'],
                'lastName' => $user['lastname'],
                'birthday' => $user['birthday'],
                'customerGroups' => $user['customergroup'],
                'addresses' => array()
            )));
            $this->Response()->sendResponse();
            exit();
        } catch (Exception $error) {
            $this->Response()->setHeader('Content-Type', 'application/json');
            $this->Response()->setBody(json_encode($error->getMessage()));

            $this->Response()->sendResponse();
            exit();
        }
    }

    /**
     * Custom action to update user data
     */
    public function updateUserAction()
    {
        $this->Response()->setHeader('Content-Type', 'application/json');

        $response = array(
            'success' => true,
            'message' => ''
        );

        try {
            $params = $this->getJsonParams();
            $decoded = $this->getJWT($params['token']);

            $customer = $this->getCustomer($decoded['customer_id']);
            $customer->setFirstname($decoded['first_name']);
            $customer->setLastname($decoded['last_name']);
            $customer->setAttribute($decoded['custom_attributes']);

            Shopware()->Models()->persist($customer);
            Shopware()->Models()->flush();

            $response['success'] = true;
            $response['message'] = $decoded['email'];

        } catch (Exception $error) {
            $response['message'] = $error->getMessage();
        }

        $this->Response()->setBody(json_encode($response));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom action to update user email
     */
    public function updateUserEmailAction()
    {
        if (!$this->Request()->isPut()) {
            return;
        }

        $this->Response()->setHeader('Content-Type', 'application/json');

        $response = array(
            'success' => false,
            'message' => ''
        );

        try {
            $params = $this->getJsonParams();
            $decoded = $this->getJWT($params['token']);

            $customer = $this->getCustomer($decoded['customer_id']);

            $form = $this->createForm("Shopware\\Bundle\\AccountBundle\\Form\Account\\EmailUpdateFormType", $customer);
            $emailData = array(
                'email' => $decoded['email'],
                'emailConfirmation' => $decoded['email']
            );
            $form->submit($emailData, false);

            if ($form->isValid()) {
                $customerService = Shopware()->Container()->get('shopware_account.customer_service');
                $customerService->update($customer);
                $response['success'] = true;
            } else {
                $errors = $form->getErrors(true);
                $string = '';
                foreach ($errors as $error) {
                    $string .= $error->getMessage()."\n";
                }
                $response['message'] = $string;
            }

        } catch (Exception $error) {
            $response['message'] = $error->getMessage();
        }

        $this->Response()->setBody(json_encode($response));

        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom action to update user password
     */
    public function updateUserPasswordAction()
    {
        if (!$this->Request()->isPut()) {
            return;
        }

        $this->Response()->setHeader('Content-Type', 'application/json');

        $response = array(
            'success' => false,
            'message' => ''
        );

        $params = $this->getJsonParams();
        $decoded = $this->getJWT($params['token']);

        $customer = $this->getCustomer($decoded['customer_id']);

        $this->get('session')->offsetSet('sUserPassword', $customer->getPassword());

        $form = $this->createForm("Shopware\\Bundle\\AccountBundle\\Form\Account\\PasswordUpdateFormType", $customer);
        $passwordData = array(
            'password' => $decoded['password'],
            'passwordConfirmation' => $decoded['password'],
            'currentPassword' => $decoded['old_password']
        );
        $form->submit($passwordData);

        if ($form->isValid()) {
            $customerService = Shopware()->Container()->get('shopware_account.customer_service');
            $customerService->update($customer);
            $response['success'] = true;
        } else {
            $errors = $form->getErrors(true);
            $string = '';
            foreach ($errors as $error) {
                $string .= $error->getMessage()."\n";
            }
            $response['message'] = $string;
        }

        $this->Response()->setBody(json_encode($response));

        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom registration action which checks whether the user is inside the app's web view
     */
    public function registrationAction()
    {
        $path = $this->Request()->getPathInfo();
        $segments = explode('/', trim($path, '/'));

        $sessionId = $segments[2];

        if (isset($sessionId)) {
            session_write_close();
            session_id($sessionId);
            session_start(array(
                'sessionId' => $sessionId
            ));
        }

        $sgCloud = $this->Request()->getParam('sgcloud_checkout');

        $this->session->offsetSet('sgWebView', true);
        $this->Response()->setHeader('Set-Cookie', 'sgWebView=true; path=/; HttpOnly');

        if (isset($sgCloud)) {
            $this->Response()->setHeader('Set-Cookie', 'session-1='.$sessionId.'; path=/; HttpOnly');
            $this->redirect('checkout/confirm#show-registration');
        } else {
            $this->redirect('account#show-registration');
        }
    }

    /**
     * Custom action to get the address data of an user
     */
    public function getAddressesAction()
    {
        $decoded = $this->getJWT($this->Request()->getCookie('token'));
        $customer = $this->getCustomer($decoded['customer_id']);

        $defaultBillingAddress = $customer->getDefaultBillingAddress();
        $defaultShippingAddress = $customer->getDefaultShippingAddress();

        $addressRepository = Shopware()->Models()->getRepository("Shopware\\Models\\Customer\\Address");

        $addresses = $addressRepository->getListArray($customer->getId());

        // Create a list of ids of occurring countries and states
        $countryIds = array_unique(array_filter(array_column($addresses, 'countryId')));
        $stateIds = array_unique(array_filter(array_column($addresses, 'stateId')));

        $countryRepository = $this->container->get('shopware_storefront.country_gateway');
        $context = $this->container->get('shopware_storefront.context_service')->getShopContext();

        $countries = $countryRepository->getCountries($countryIds, $context);
        $states = $countryRepository->getStates($stateIds, $context);

        // Apply translations for countries and states to address array, converting them from structs to arrays in the process
        foreach ($addresses as &$address) {
            if (array_key_exists($address['countryId'], $countries)) {
                $address['country'] = json_decode(json_encode($countries[$address['countryId']]), true);
            }
            if (array_key_exists($address['stateId'], $states)) {
                $address['state'] = json_decode(json_encode($states[$address['stateId']]), true);
            }

            $customerAddress = $addressRepository->getOneByUser($address['id'], $customer->getId());
            $address['additional'] = $customerAddress->getAdditional();
            $address['defaultBillingAddress'] = $defaultBillingAddress->getId() === $address['id'];
            $address['defaultShippingAddress'] = $defaultShippingAddress->getId() === $address['id'];
        }
        unset($address);

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($addresses));

        $this->Response()->sendResponse();
        exit();
    }

    public function addAddressAction()
    {
        if (!$this->Request()->isPost()) {
            return;
        }

        $params = $this->getJsonParams();
        $data = $this->getJWT($params['token']);
        $customer = $this->getCustomer($data['customer_id']);

        $this->Response()->setHeader('Content-Type', 'application/json');

        if (!empty($data['address']['country'])) {
            $query = Shopware()->Models()->getConnection()->createQueryBuilder();
            $query->select('id')
                ->from('s_core_countries', 'country')
                ->where('country.countryiso = :iso')
                ->setParameter('iso', $data['address']['country']);

            $countryId = $query->execute()->fetch();
            $data['address']['country'] = $countryId['id'];
        }

        if (!empty($data['address']['state'])) {
            $query = Shopware()->Models()->getConnection()->createQueryBuilder();
            $query->select('id')
                ->from('s_core_countries_states', 'state')
                ->where('state.countryID = :id')
                ->andwhere('state.shortcode = :code')
                ->setParameter('id', $countryId['id'])
                ->setParameter('code', $data['address']['state']);

            $stateId = $query->execute()->fetch();
            $data['address']['state'] = $stateId['id'];
        }

        $address = new \Shopware\Models\Customer\Address();
        $form = $this->createForm('Shopware\\Bundle\\AccountBundle\\Form\\Account\\AddressFormType', $address);

        $form->submit($data['address']);

        if ($form->isValid()) {
            $addressService = Shopware()->Container()->get('shopware_account.address_service');
            $addressService->create($address, $customer);

            $additional = $address->getAdditional();

            if (!empty($additional['setDefaultBillingAddress'])) {
                $addressService->setDefaultBillingAddress($address);
            }

            if ($this->isValidShippingAddress($address) && !empty($additional['setDefaultShippingAddress'])) {
                $addressService->setDefaultShippingAddress($address);
            }
            $this->Response()->setBody(json_encode(array('success' => true)));
        } else {

            $errors = $form->getErrors(true);
            $string = '';
            foreach ($errors as $error) {
                $string .= $error->getOrigin()->getName().$error->getMessage()."\n";
            }

            $this->Response()->setBody(json_encode(array(
                'success' => false,
                'message' => $string
            )));
        }

        $this->Response()->sendResponse();
        exit();
    }

    public function updateAddressAction()
    {
        if (!$this->Request()->isPut()) {
            return;
        }

        $this->Response()->setHeader('Content-Type', 'application/json');

        $params = $this->getJsonParams();
        $data = $this->getJWT($params['token']);
        $customer = $this->getCustomer($data['customer_id']);

        if (!empty($data['address']['country'])) {
            $query = Shopware()->Models()->getConnection()->createQueryBuilder();
            $query->select('id')
                ->from('s_core_countries', 'country')
                ->where('country.countryiso = :iso')
                ->setParameter('iso', $data['address']['country']);

            $countryId = $query->execute()->fetch();
            $data['address']['country'] = $countryId['id'];
        }

        if (!empty($data['address']['state'])) {
            $query = Shopware()->Models()->getConnection()->createQueryBuilder();
            $query->select('id')
                ->from('s_core_countries_states', 'state')
                ->where('state.countryID = :id')
                ->andwhere('state.shortcode = :code')
                ->setParameter('id', $countryId['id'])
                ->setParameter('code', $data['address']['state']);

            $stateId = $query->execute()->fetch();
            $data['address']['state'] = $stateId['id'];
        }

        $addressRepository = Shopware()->Models()->getRepository("Shopware\\Models\\Customer\\Address");
        $address = $addressRepository->getOneByUser((int)$data['address']['id'], $customer->getId());

        $form = $this->createForm('Shopware\\Bundle\\AccountBundle\\Form\\Account\\AddressFormType', $address);
        $form->submit($data['address']);

        if ($form->isValid()) {
            $addressService = Shopware()->Container()->get('shopware_account.address_service');
            $addressService->create($address, $customer);

            $additional = $address->getAdditional();

            if (!empty($additional['setDefaultBillingAddress'])) {
                $addressService->setDefaultBillingAddress($address);
            }

            if ($this->isValidShippingAddress($address) && !empty($additional['setDefaultShippingAddress'])) {
                $addressService->setDefaultShippingAddress($address);
            }
            $this->Response()->setBody(json_encode(array('success' => true)));
        } else {

            $errors = $form->getErrors(true);
            $string = '';
            foreach ($errors as $error) {
                $string .= $error->getOrigin()->getName().$error->getMessage()."\n";
            }

            $this->Response()->setBody(json_encode(array(
                'success' => false,
                'message' => $string
            )));
        }

        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom address action to get delete an address
     */
    public function deleteAddressAction()
    {
        if (!$this->Request()->isDelete()) {
            return;
        }

        $params = $this->getJsonParams();
        $data = $this->getJWT($params['token']);
        $customer = $this->getCustomer($data['customer_id']);
        $addressService = Shopware()->Container()->get('shopware_account.address_service');
        $addressRepository = Shopware()->Models()->getRepository("Shopware\\Models\\Customer\\Address");

        $this->Response()->setHeader('Content-Type', 'application/json');

        foreach ($data['addressIds'] as $addressId) {
            $address = $addressRepository->getOneByUser((int)$addressId, $customer->getId());
            $addressService->delete($address);
        }

        $this->Response()->setBody(json_encode(array('success' => true)));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom favorite action to get the favorite list of an user
     */
    public function getFavoritesAction()
    {
        $decoded = $this->getJWT($this->Request()->getCookie('token'));
        $sql = ' SELECT id FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';

        $userId = $this->db->fetchRow($sql, array($decoded['customer_id'])) ?: array();
        $this->session->offsetSet('sUserId', $userId['id']);

        $nodes = Shopware()->Modules()->Basket()->sGetNotes();

        if ($this->isWishList()) {
            $builder = $this->getModelManager()->createQueryBuilder();
            $builder->select(array('cart', 'items', 'details'))
                ->from('SwagAdvancedCart\Models\Cart\Cart', 'cart')
                ->leftJoin('cart.customer', 'customer')
                ->leftJoin('cart.cartItems', 'items')
                ->leftJoin('items.details', 'details')
                ->where('customer.id = :customerId')
                ->andWhere('cart.shopId = :shopId')
                ->andWhere('LENGTH(cart.name) > 0')
                ->orderBy('items.id', 'DESC')
                ->setParameter('customerId', $userId['id'])
                ->setParameter('shopId', $this->get('shop')->getId());

            $carts = $builder->getQuery()->getResult();
            $nodes = $this->prepareCartItems($carts[0]);
        }

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($nodes));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom favorite action to add a product to the favorite list of an user
     */
    public function addToFavoriteListAction()
    {
        $this->Response()->setHeader('Content-Type', 'application/json');

        if (!$this->Request()->isPost()) {
            $this->Response()->setBody(json_encode(array('success' => false)));
            $this->Response()->sendResponse();
            exit();
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->getJsonParams();

        $decoded = $this->getJWT($params['token']);

        $sql = ' SELECT id FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';
        $userId = $this->db->fetchRow($sql, array($decoded['customerId'])) ?: array();
        $this->session->offsetSet('sUserId', $userId['id']);

        if ($this->addArticleToWishList($decoded['articles'], $userId['id'])) {
            $this->Response()->setBody(json_encode(array('success' => true)));
            $this->Response()->sendResponse();
            exit();
        }

        $this->Response()->setBody(json_encode(array('success' => false)));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom favorite action to delete a product from the favorite list of an user
     */
    public function deleteFromFavoriteListAction()
    {
        $this->Response()->setHeader('Content-Type', 'application/json');

        if (!$this->Request()->isDelete()) {
            $this->Response()->setBody(json_encode(array('success' => false)));
            $this->Response()->sendResponse();
            exit();
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->getJsonParams();

        $decoded = $this->getJWT($params['token']);

        $sql = ' SELECT id FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';
        $userId = $this->db->fetchRow($sql, array($decoded['customerId'])) ?: array();
        $this->session->offsetSet('sUserId', $userId['id']);

        if ($this->removeProductFromWishList($decoded['articles'], $userId['id'])) {
            $this->Response()->setBody(json_encode(array('success' => true)));
            $this->Response()->sendResponse();
            exit();
        }

        $this->Response()->setBody(json_encode(array('success' => false)));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * @param \Shopware\Models\Customer\Address $address
     * @return bool
     */
    private function isValidShippingAddress(\Shopware\Models\Customer\Address $address)
    {
        $additional = $address->getAdditional();

        if (empty($additional['setDefaultShippingAddress']) && $address->getId() !== $address->getCustomer()->getDefaultShippingAddress()->getId()) {
            return true;
        }

        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getContext();
        $country = Shopware()->Container()->get('shopware_storefront.country_gateway')->getCountry($address->getCountry()->getId(), $context);

        return $country->allowShipping();
    }

    /**
     * A helper function that to delete articles from wish list or note list
     *
     * @param $articles
     * @param $userId
     * @return bool
     */
    protected function removeProductFromWishList($articles, $userId)
    {
        foreach ($articles as $article) {
            $articleId = trim($article['product_id']);
            $orderNumber = trim($article['variant_id']);

            $product = Shopware()->Modules()->Articles()->sGetArticleById($articleId);

            if ($product) {
                if ($orderNumber === "") {
                    $orderNumber = $product['ordernumber'];
                }

                if ($this->isWishList()) {
                    $builder = $this->getModelManager()->createQueryBuilder();
                    $builder->select(array('cart', 'items', 'details'))
                        ->from('SwagAdvancedCart\Models\Cart\Cart', 'cart')
                        ->leftJoin('cart.customer', 'customer')
                        ->leftJoin('cart.cartItems', 'items')
                        ->leftJoin('items.details', 'details')
                        ->where('customer.id = :customerId')
                        ->andWhere('cart.shopId = :shopId')
                        ->andWhere('LENGTH(cart.name) > 0')
                        ->orderBy('items.id', 'DESC')
                        ->setParameter('customerId', $userId)
                        ->setParameter('shopId', $this->get('shop')->getId());

                    $carts = $builder->getQuery()->getResult();
                    $cart = $carts[0];
                    $cartId = $cart->getId();

                    if (empty($cartId)) {
                        return false;
                    }

                    $builder = $this->getModelManager()->createQueryBuilder();
                    $builder->select('item')
                        ->from('SwagAdvancedCart\Models\Cart\CartItem', 'item')
                        ->where('item.productOrderNumber = :itemId')
                        ->andWhere('item.basket_id = :basketId')
                        ->innerJoin('item.cart', 'cart')
                        ->innerJoin('cart.customer', 'customer')
                        ->andWhere('customer.id = :userId')
                        ->setParameter('itemId', $orderNumber)
                        ->setParameter('basketId', $cartId)
                        ->setParameter('userId', $userId);

                    $cartItem = $builder->getQuery()->getOneOrNullResult();

                    if (empty($cartItem)) {
                        return false;
                    }

                    $cart->setModified(date('Y-m-d H:i:s'));

                    $modelManager = $this->getModelManager();
                    $modelManager->remove($cartItem);
                    $modelManager->flush();

                } else {
                    $delete = Shopware()->Db()->query(
                        'DELETE FROM s_order_notes 
                        WHERE userID = ? AND ordernumber = ?',
                        array((int)$userId, $orderNumber)
                    );

                    if (!$delete) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * A helper function that to add articles to wish list or note
     *
     * @param $articles
     * @param $userId
     * @return bool
     */
    protected function addArticleToWishList($articles, $userId)
    {
        foreach ($articles as $article) {
            $articleId = trim($article['product_id']);
            $orderNumber = trim($article['variant_id']);

            $product = Shopware()->Modules()->Articles()->sGetArticleById($articleId);

            if ($product) {
                if ($orderNumber === "") {
                    $orderNumber = $product['ordernumber'];
                }

                if ($this->isWishList()) {

                    $builder = $this->getModelManager()->createQueryBuilder();
                    $builder->select(array('cart', 'items', 'details'))
                        ->from('SwagAdvancedCart\Models\Cart\Cart', 'cart')
                        ->leftJoin('cart.customer', 'customer')
                        ->leftJoin('cart.cartItems', 'items')
                        ->leftJoin('items.details', 'details')
                        ->where('customer.id = :customerId')
                        ->andWhere('cart.shopId = :shopId')
                        ->andWhere('LENGTH(cart.name) > 0')
                        ->orderBy('items.id', 'DESC')
                        ->setParameter('customerId', $userId)
                        ->setParameter('shopId', $this->get('shop')->getId());

                    $carts = $builder->getQuery()->getResult();

                    if (empty($carts)) {
                        $this->Request()->setPost('newlist', 'App');
                    } else {
                        $this->Request()->setPost('lists', array($carts[0]->getId()));
                    }

                    $this->Request()->setPost('ordernumber', $orderNumber);
                    $this->container->get('swag_advanced_cart.cart_handler')->addToList($this->Request()->getPost());
                } else {
                    $productId = Shopware()->Modules()->Articles()->sGetArticleIdByOrderNumber($orderNumber);
                    $productName = Shopware()->Modules()->Articles()->sGetArticleNameByOrderNumber($orderNumber);

                    if (empty($productId)) {
                        return false;
                    }

                    Shopware()->Modules()->Basket()->sAddNote($productId, $productName, $orderNumber);
                }
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * A helper function to check if the wish list from the advanced cart plugin is active
     *
     * @return bool
     */
    private function isWishList()
    {
        $pluginManager  = $this->container->get('shopware_plugininstaller.plugin_manager');
        $plugin = $pluginManager->getPluginByName('SwagAdvancedCart');
        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('SwagAdvancedCart');

        if ($plugin->getInstalled() && $plugin->getActive() && $config['replaceNote']) {
            return true;
        }

        return false;
    }

    /**
     * A helper function that prepares a single cart
     *
     * @param Cart $cart
     *
     * @return null|array
     */
    private function prepareCartItems($cart)
    {
        $items = array();

        /** @var CartItem $cartItem */
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->getDetail() || !$cartItem->getDetail()->getActive()) {
                continue;
            }
            $orderNumber = $cartItem->getProductOrderNumber();
            $product = $this->prepareArticle($orderNumber, $cartItem);

            if ($product) {
                $items[] = $product;
            }
        }

        return $items;
    }

    /**
     * A helper function that returns a prepared product object ready to be displayed in the frontend
     *
     * @param int      $orderNumber
     * @param CartItem $cartItem
     *
     * @return null|array
     */
    private function prepareArticle($orderNumber, $cartItem)
    {
        if (!$orderNumber) {
            return null;
        }

        //Gets the product by the order number
        $productId = $this->get('modules')->Articles()->sGetArticleIdByOrderNumber($orderNumber);

        if (!$productId) {
            return null;
        }

        try {
            $product = $this->get('modules')->Articles()->sGetArticleById($productId, null, $orderNumber);
        } catch (\RuntimeException $e) {
            // if product is not found the ProductNumberService will throw an exception
            return null;
        }

        if (!$product || !$this->existsInMainCategory($productId)) {
            return null;
        }

        $sql = 'SELECT active FROM s_articles_details WHERE ordernumber = :orderNumber;';
        $isVariantActive = $this->get('db')->fetchOne($sql, array('orderNumber' => $orderNumber));

        if (!$isVariantActive) {
            $message = $this->get('snippets')->getNamespace('frontend/plugins/swag_advanced_cart/plugin')->get('ArticleNotAvailable');
            $items[] = array(
                'id' => $cartItem->getId(),
                'ordernumber' => $cartItem->getProductOrderNumber(),
                'quantity' => $cartItem->getQuantity(),
                'name' => $message,
                'article' => array(
                    'articleName' => $message,
                    'articlename' => $message,
                ),
            );

            return null;
        }

        $product['price'] = str_replace(',', '.', $product['price']) * $cartItem->getQuantity();

        //For compatibility issues
        $product['articlename'] = $product['articleName'];

        if ($product['sConfigurator']) {
            if ($product['additionaltext']) {
                $product['articlename'] .= ' ' . $product['additionaltext'];
                $product['articleName'] .= ' ' . $product['additionaltext'];
            } else {
                $productName = $this->get('modules')->Articles()->sGetArticleNameByOrderNumber($orderNumber);
                $product['articlename'] = $productName;
                $product['articleName'] = $productName;
            }
        }

        $product['img'] = $product['image']['src'][0];

        return $product;
    }

    /**
     * check for product exists in active category
     *
     * @param $productId
     *
     * @return mixed
     */
    private function existsInMainCategory($productId)
    {
        $categoryId = $this->get('shop')->getCategory()->getId();

        $exist = $this->get('db')->fetchRow(
            'SELECT * FROM s_articles_categories_ro WHERE categoryID = ? AND articleID = ?',
            array($categoryId, $productId)
        );

        return $exist;
    }

    /**
     * @param $customerId
     * @return Shopware\\Models\\Customer\\Customer $customer
     */
    protected function getCustomer($customerId) {
        $sql = ' SELECT id FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';

        $userId = $this->db->fetchRow($sql, array($customerId)) ?: array();
        $this->session->offsetSet('sUserId', $userId['id']);

        return Shopware()->Models()->find("Shopware\\Models\\Customer\\Customer", $userId['id']);
    }

    /**
     * @param $token
     * @return array
     */
    protected function getJWT($token) {
        $key = trim($this->getConfig()->getApikey());
        JWT::$leeway = 60;
        $decoded = JWT::decode($token, $key, array('HS256'));

        return json_decode(json_encode($decoded), true);
    }

    /**
     * Verify if user credentials are valid
     *
     * @param $email
     * @param $hash
     * @return array
     */
    protected function verifyUser($email, $hash)
    {
        if (empty($email)) {
            $sErrorFlag['email'] = true;
        }
        if (empty($hash)) {
            $sErrorFlag['password'] = true;
        }

        $addScopeSql = '';
        if ($this->scopedRegistration == true) {
            $addScopeSql = $this->db->quoteInto(' AND subshopID = ? ', $this->subshopId);
        }

        $preHashedSql = $this->db->quoteInto(' AND password = ? ', $hash);

        $sql = '
                SELECT id, customergroup, password, encoder
                FROM s_user WHERE email = ? AND active=1
                AND (lockeduntil < now() OR lockeduntil IS NULL) '
            . $addScopeSql
            . $preHashedSql;

        $getUser = $this->db->fetchRow($sql, array($email)) ?: array();

        if (!count($getUser)) {
            $isValidLogin = false;
        } else {
            $encoderName = 'Prehashed';

            $plaintext = $hash;
            $password = $getUser['password'];

            $isValidLogin = $this->passwordEncoder->isPasswordValid($plaintext, $password, $encoderName);
        }

        if (!$isValidLogin) {
            $sErrorMessages = array();
            $sErrorMessages['sErrorMessages'] = 'your account is invalid';
            return $sErrorMessages;
        }

        $userId = $getUser['id'];
        $sql = '
            SELECT * FROM s_user
            WHERE password = ? AND email = ? AND id = ?
            AND UNIX_TIMESTAMP(lastlogin) >= (UNIX_TIMESTAMP(now())-?)
        ';

        $user = $this->db->fetchRow(
            $sql, array($hash, $email, $userId, 7200,)
        );

        return $user;
    }

    /**
     * @return Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config
     */
    protected function getConfig()
    {
        static $config = null;

        if (!$config) {
            $config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        }

        return $config;
    }

    /**
     * WARNING: ALL functions listed below have been directly copied from Shopware's Checkout or Basket controller.
     */

    /**
     * Get shipping costs as an array (brutto / netto) depending on selected country / payment
     *
     * @return array
     */
    protected function getShippingCosts()
    {
        $country = $this->getSelectedCountry();
        $payment = $this->getSelectedPayment();
        if (empty($country) || empty($payment)) {
            return array('brutto' => 0, 'netto' => 0);
        }
        $shippingcosts = $this->admin->sGetPremiumShippingcosts($country);

        return empty($shippingcosts) ? array('brutto' => 0, 'netto' => 0) : $shippingcosts;
    }

    /**
     * Get current selected country - if no country is selected, choose first one from list
     * of available countries
     *
     * @return array with country information
     */
    protected function getSelectedCountry()
    {
        if (!empty($this->View()->sUserData['additional']['countryShipping'])) {
            $this->session['sCountry'] = (int) $this->View()->sUserData['additional']['countryShipping']['id'];
            $this->session['sArea'] = (int) $this->View()->sUserData['additional']['countryShipping']['areaID'];

            return $this->View()->sUserData['additional']['countryShipping'];
        }
        $countries = $this->getCountryList();
        if (empty($countries)) {
            unset($this->session['sCountry']);

            return false;
        }
        $country = reset($countries);
        $this->session['sCountry'] = (int) $country['id'];
        $this->session['sArea'] = (int) $country['areaID'];
        $this->View()->sUserData['additional']['countryShipping'] = $country;

        return $country;
    }

    /**
     * Get all countries from database via sAdmin object
     *
     * @return array list of countries
     */
    protected function getCountryList()
    {
        return $this->admin->sGetCountryList();
    }

    /**
     * Returns all available payment methods from sAdmin object
     *
     * @return array list of payment methods
     */
    protected function getPayments()
    {
        return $this->admin->sGetPaymentMeans();
    }

    /**
     * Get selected payment or do payment mean selection automatically
     *
     * @return array
     */
    protected function getSelectedPayment()
    {
        $paymentMethods = $this->getPayments();

        if (!empty($this->View()->sUserData['additional']['payment'])) {
            $payment = $this->View()->sUserData['additional']['payment'];
        } elseif (!empty($this->session['sPaymentID'])) {
            $payment = $this->admin->sGetPaymentMeanById($this->session['sPaymentID'], $this->View()->sUserData);
        }

        if ($payment && !$this->checkPaymentAvailability($payment, $paymentMethods)) {
            $payment = null;
        }

        $paymentClass = $this->admin->sInitiatePaymentClass($payment);
        if ($payment && $paymentClass instanceof \ShopwarePlugin\PaymentMethods\Components\BasePaymentMethod) {
            $data = $paymentClass->getCurrentPaymentDataAsArray(Shopware()->Session()->sUserId);
            $payment['validation'] = $paymentClass->validate($data);
            if (!empty($data)) {
                $payment['data'] = $data;
            }
        }

        if (!empty($payment)) {
            return $payment;
        }

        if (empty($paymentMethods)) {
            unset($this->session['sPaymentID']);

            return false;
        }

        $payment = $this->getDefaultPaymentMethod($paymentMethods);

        $this->session['sPaymentID'] = (int) $payment['id'];
        $this->front->Request()->setPost('sPayment', (int) $payment['id']);
        $this->admin->sUpdatePayment();

        //if customer logged in and payment switched to fallback, display cart notice. Otherwise anonymous customers will see the message too
        if (Shopware()->Session()->sUserId) {
            $this->flagPaymentBlocked();
        }

        return $payment;
    }

    /**
     * Selects the default payment method defined in the backend. If no payment method is defined,
     * the first payment method of the provided list will be returned.
     *
     * @param array $paymentMethods
     *
     * @return array
     */
    private function getDefaultPaymentMethod(array $paymentMethods)
    {
        $payment = null;

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod['id'] == Shopware()->Config()->offsetGet('defaultpayment')) {
                $payment = $paymentMethod;
                break;
            }
        }

        if (!$payment) {
            $payment = reset($paymentMethods);
        }

        return $payment;
    }

    /**
     * checks if the current user selected an available payment method
     *
     * @param array $currentPayment
     * @param array $payments
     *
     * @return bool
     */
    private function checkPaymentAvailability($currentPayment, $payments)
    {
        foreach ($payments as $availablePayment) {
            if ($availablePayment['id'] === $currentPayment['id']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns tax rates for all basket positions
     *
     * @param array $basket array returned from this->getBasket
     *
     * @return array
     */
    private function getTaxRates($basket)
    {
        $result = array();

        if (!empty($basket['sShippingcostsTax'])) {
            $basket['sShippingcostsTax'] = number_format((float) $basket['sShippingcostsTax'], 2);

            $result[$basket['sShippingcostsTax']] = $basket['sShippingcostsWithTax'] - $basket['sShippingcostsNet'];
            if (empty($result[$basket['sShippingcostsTax']])) {
                unset($result[$basket['sShippingcostsTax']]);
            }
        }

        if (empty($basket['content'])) {
            ksort($result, SORT_NUMERIC);

            return $result;
        }

        foreach ($basket['content'] as $item) {
            if (!empty($item['tax_rate'])) {
            } elseif (!empty($item['taxPercent'])) {
                $item['tax_rate'] = $item['taxPercent'];
            } elseif ($item['modus'] == 2) {
                // Ticket 4842 - dynamic tax-rates
                $resultVoucherTaxMode = Shopware()->Db()->fetchOne(
                    'SELECT taxconfig FROM s_emarketing_vouchers WHERE ordercode=?
                ', array($item['ordernumber']));
                // Old behaviour
                if (empty($resultVoucherTaxMode) || $resultVoucherTaxMode === 'default') {
                    $tax = Shopware()->Config()->get('sVOUCHERTAX');
                } elseif ($resultVoucherTaxMode === 'auto') {
                    // Automatically determinate tax
                    $tax = $this->basket->getMaxTax();
                } elseif ($resultVoucherTaxMode === 'none') {
                    // No tax
                    $tax = '0';
                } elseif ((int) $resultVoucherTaxMode) {
                    // Fix defined tax
                    $tax = Shopware()->Db()->fetchOne('
                    SELECT tax FROM s_core_tax WHERE id = ?
                    ', array($resultVoucherTaxMode));
                }
                $item['tax_rate'] = $tax;
            } else {
                // Ticket 4842 - dynamic tax-rates
                $taxAutoMode = Shopware()->Config()->get('sTAXAUTOMODE');
                if (!empty($taxAutoMode)) {
                    $tax = $this->basket->getMaxTax();
                } else {
                    $tax = Shopware()->Config()->get('sDISCOUNTTAX');
                }
                $item['tax_rate'] = $tax;
            }

            if (empty($item['tax_rate']) || empty($item['tax'])) {
                continue;
            } // Ignore 0 % tax

            $taxKey = number_format((float) $item['tax_rate'], 2);

            $result[$taxKey] += str_replace(',', '.', $item['tax']);
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * Used in ajax add cart action
     * Check availability of product and return info / error - messages
     *
     * @param string $orderNumber article order number
     * @param int    $quantity    quantity
     *
     * @return string|null
     */
    private function getInstockInfo($orderNumber, $quantity)
    {
        if (empty($orderNumber)) {
            return Shopware()->Snippets()->getNamespace('frontend')->get('CheckoutSelectVariant',
                'Please select an option to place the required product in the cart', true);
        }

        $quantity = max(1, (int) $quantity);
        $inStock = $this->getAvailableStock($orderNumber);

        $inStock['quantity'] = $quantity;

        if (empty($inStock['articleID'])) {
            return Shopware()->Snippets()->getNamespace('frontend')->get('CheckoutArticleNotFound',
                'Product could not be found.', true);
        }
        if (!empty($inStock['laststock']) || !empty(Shopware()->Config()->InstockInfo)) {
            if ((int)$inStock['instock'] <= 0 && !empty($inStock['laststock'])) {
                return Shopware()->Snippets()->getNamespace('frontend')->get('CheckoutArticleNoStock',
                    'Unfortunately we can not deliver the desired product in sufficient quantity', true);
            } elseif ((int)$inStock['instock'] < (int)$inStock['quantity']) {
                $result = 'Unfortunately we can not deliver the desired product in sufficient quantity. (#0 of #1 in stock).';
                $result = Shopware()->Snippets()->getNamespace('frontend')->get('CheckoutArticleLessStock', $result,
                    true);

                return str_replace(array('#0', '#1'), array($inStock['instock'], $inStock['quantity']), $result);
            }
        }

        return null;
    }

    /**
     * Get current stock from a certain product defined by $ordernumber
     * Support for multidimensional variants
     *
     * @param unknown_type $ordernumber
     *
     * @return array with article id / current basket quantity / instock / laststock
     */
    private function getAvailableStock($ordernumber)
    {
        $sql = '
            SELECT
                a.id as articleID,
                ob.quantity,
                IF(ad.instock < 0, 0, ad.instock) as instock,
                a.laststock,
                ad.ordernumber as ordernumber
            FROM s_articles a
            LEFT JOIN s_articles_details ad
            ON ad.ordernumber=?
            LEFT JOIN s_order_basket ob
            ON ob.sessionID=?
            AND ob.ordernumber=ad.ordernumber
            AND ob.modus=0
            WHERE a.id=ad.articleID
        ';
        $row = Shopware()->Db()->fetchRow($sql, array(
            $ordernumber,
            Shopware()->Session()->get('sessionId'),
        ));

        return $row;
    }
}
