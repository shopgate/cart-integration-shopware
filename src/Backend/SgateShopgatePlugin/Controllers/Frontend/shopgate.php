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
            'accountOrders'
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
        $userId = $params['userId'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if(!empty($userId)) {
            $this->session->offsetSet('sUserId', $userId);
        }

        if (isset($promotionVouchers)) {
            $this->session->offsetSet('promotionVouchers', $promotionVouchers);
        }

        $response['addVoucher'] = $this->basket->sAddVoucher($code);
        $response['promotionVouchers'] = json_encode($this->session->get('promotionVouchers'));

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($response));
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

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if (isset($promotionVouchers) && $voucher) {
            $this->session->offsetSet('promotionVouchers', $promotionVouchers);
            $sql = 'SELECT DISTINCT `ordernumber` FROM `s_order_basket` WHERE id=?';
            $orderNumber = Shopware()->Db()->fetchCol($sql, array($articleId));

            $sql = 'SELECT 1 FROM `s_plugin_promotion` LIMIT 1';
            $test = Shopware()->Db()->fetchCol($sql);
            $response['test'] = $test;
            if ($test) {

                $sql = 'SELECT DISTINCT `id` FROM `s_plugin_promotion` WHERE number=?';
                $voucherId = Shopware()->Db()->fetchCol($sql, $orderNumber);

                if ($voucherId) {
                    $this->session->offsetSet('promotionVouchers', $promotionVouchers[$voucherId]);
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
        $voucher = $this->basket->sGetVoucher();

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

            if (!empty($basket['content'])) {
                $this->basket->clearBasket();

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
        $token = $this->Request()->getCookie('token');

        $key = trim($this->getConfig()->getApikey());
        JWT::$leeway = 60;
        $decoded = JWT::decode($token, $key, array('HS256'));
        $decoded = json_decode(json_encode($decoded), true);
        $customerId = $decoded['customer_id'];

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
