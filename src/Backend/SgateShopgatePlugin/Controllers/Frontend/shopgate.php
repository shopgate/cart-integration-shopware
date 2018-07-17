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
     * Reference to Shopware session object (Shopware()->Session)
     *
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

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

        $this->contextService = $container->get('shopware_storefront.context_service');
        $this->productService = $container->get('shopware_storefront.product_service');
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
            'addCouponsCode'
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
     *
     */
    public function getCartAction()
    {
        $sessionId = $this->Request()->getCookie('sg_session');

        $this->session->offsetSet('sessionId', $sessionId);
        session_id($sessionId);

        $basket = $this->basket->sGetBasket();

        if (!isset($basket)) {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setHeader('Content-Type', 'application/json');
            $this->Response()->setBody(json_encode($basket));
            $this->Response()->sendResponse();
            exit();
        }

        $shippingcosts = $this->getShippingCosts();

        $currency = $this->get('shop')->getCurrency();

        $basket['sCurrencyId'] = $currency->getId();
        $basket['sCurrencyName'] = $currency->getCurrency();
        $basket['sCurrencyFactor'] = $currency->getFactor();

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
        if ((!Shopware()->System()->sUSERGROUPDATA['tax'] && Shopware()->System()->sUSERGROUPDATA['id'])) {
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
     *
     */
    public function checkoutAction()
    {
        $sessionId = $this->Request()->getParam('sessionId');

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        $token = $this->Request()->getParam('token');
        if (isset($token)) {
            $key = trim($this->getConfig()->getApikey());
            $decoded = static::jwtDecode($token, $key);
            $decoded = json_decode(json_encode($decoded), true);
            $customerId = $decoded['customer_id'];

            $sql = 'SELECT DISTINCT `password` FROM `s_user` WHERE customernumber=?';
            $password = Shopware()->Db()->fetchCol($sql, [$customerId]);

            $sql = 'SELECT DISTINCT `email` FROM `s_user` WHERE customernumber=?';
            $email = Shopware()->Db()->fetchCol($sql, [$customerId]);

            $this->Request()->setPost('email', $email[0]);
            $this->Request()->setPost('passwordMD5', $password[0]);

            $checkUser = $this->admin->sLogin(true);

            if(isset($checkUser['sErrorFlag'])) {
                throw new Exception($checkUser['sErrorMessages'][0] , 400);
            }

            $this->basket->sRefreshBasket();
        }

        $this->Response()->setHeader('Set-Cookie', 'session-1='.$this->session->offsetGet('sessionId').'; path=/; HttpOnly');
        $this->Response()->setHeader('Set-Cookie', 'sgWebView=true; path=/; HttpOnly');
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

        if (!isset($articles)) {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setBody(json_encode(['message' => 'The request doesn\'t contain an \'articles\' parameter!']));
        }

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        $response = $this->addArticlesToCart($articles);
        if ($response) {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setBody(json_encode($response));
        } else {
            $sessionId = $this->session->get('sessionId');
            $this->Response()->setHttpResponseCode(201);
            $this->Response()->setBody(json_encode(['sessionId'=> $sessionId]));
        }
        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->sendResponse();
        exit();
    }

    public function addCouponsCodeAction()
    {
        if (!$this->Request()->isPost()) {
            return;
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->getJsonParams();

        $code = $params['couponCode'];
        $sessionId = $params['sessionId'];

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        $response = $this->basket->sAddVoucher($code);

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($response));
        $this->Response()->sendResponse();
        exit();
    }

    public function deleteCartItemAction()
    {
        if (!$this->Request()->isDelete()) {
            return;
        }

        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->getJsonParams();

        $articleId = $params['articleId'];
        $sessionId = $params['sessionId'];

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        $response = $this->basket->sDeleteArticle($articleId);

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($response));
        $this->Response()->sendResponse();
        exit();
    }

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

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        $this->Response()->setHeader('Content-Type', 'application/json');
        if ($error = $this->verifyItemStock($basketId, $quantity)) {
            $this->Response()->setHttpResponseCode(401);
            $this->Response()->setBody(json_encode([
                'error' => true,
                'reason' => $error
            ]));
        } else {
            $response = $this->basket->sUpdateArticle($basketId, $quantity);
            $this->Response()->setBody(json_encode($response));
        }

        $this->Response()->sendResponse();
        exit();
    }

    protected function verifyItemStock($basketId, $quantity)
    {
        $basket = $this->basket->sGetBasket();
        foreach ($basket['content'] as $basketItem) {
            if($basketItem['id'] === $basketId) {
                return $this->getInstockInfo($basketItem['ordernumber'], $quantity);
            }
        }
    }

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
     */
    protected function addArticlesToCart($articles)
    {
        $response = []; // Contains only errors

        foreach ($articles as $article) {

            $articleId = trim($article['product_id']);
            $orderNumber = trim($article['variant_id']);

            $product = Shopware()->Modules()->Articles()->sGetArticleById($articleId);

            if ($product) {
                if ($orderNumber === "") {
                    $orderNumber = $product['ordernumber'];
                }

                if ($infoMessage = $this->getInstockInfo($orderNumber, $article['quantity'])) {
                    $response[$articleId] = $this->getInstockInfo($orderNumber, $article['quantity']);
                }
                $this->basket->sAddArticle($orderNumber, $article['quantity']);
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

        if(isset($sessionId)) {
            //Set session id using both methods because standard shopware login merges basket with session_id
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if (isset($hash)) {
            $error = $this->admin->sLogin(true);
        } else {
            $error = $this->admin->sLogin();
        }

        $this->Response()->setHeader('Content-Type', 'application/json');

        if (!empty($error['sErrorMessages'])) {
            $this->Response()->setHttpResponseCode(401);
            $this->Response()->setBody(json_encode($error));
        } else {
            $user = $this->admin->sGetUserData();
            $user = $user['additional']['user'];

            $this->Response()->setHttpResponseCode(200);
            $this->Response()->setBody(json_encode([
                'id' => $user['customernumber'],
                'mail' => $user['email'],
                'first_name' => $user['firstname'],
                'last_name' => $user['lastname'],
                'birthday' => $user['birthday'],
                'customer_groups' => $user['customergroup'],
                'session_id' => $user['sessionID']
            ]));
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
        $decoded = static::jwtDecode($token, $key);
        $decoded = json_decode(json_encode($decoded), true);
        $customerId = $decoded['customer_id'];

        $sql = 'SELECT DISTINCT `password` FROM `s_user` WHERE customernumber=?';
        $password = Shopware()->Db()->fetchCol($sql, [$customerId]);

        $sql = 'SELECT DISTINCT `email` FROM `s_user` WHERE customernumber=?';
        $email = Shopware()->Db()->fetchCol($sql, [$customerId]);

        $this->Request()->setPost('email', $email[0]);
        $this->Request()->setPost('passwordMD5', $password[0]);

        $checkUser = $this->admin->sLogin(true);

        if(isset($checkUser['sErrorFlag'])) {
            throw new Exception($checkUser['sErrorMessages'][0] , 400);
        }

        $this->basket->sRefreshBasket();

        $user = $this->admin->sGetUserData();
        $user = $user['additional']['user'];

        $this->Response()->setBody(json_encode([
            'id' => $user['customernumber'],
            'mail' => $user['email'],
            'firstName' => $user['firstname'],
            'lastName' => $user['lastname'],
            'birthday' => $user['birthday'],
            'customerGroups' => $user['customergroup'],
            'addresses' => []
        ]));
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
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
            $this->Response()->setHeader('Set-Cookie', 'session-1='.$sessionId.'; path=/; HttpOnly');
        }

        $this->session->offsetSet('sgWebView', true);
        $sgCloud = $this->Request()->getParam('sgcloud_checkout');

        $this->session->offsetSet('sgWebView', true);
        $this->Response()->setHeader('Set-Cookie', 'sgWebView=true; path=/; HttpOnly');

        if(isset($sgCloud)) {
            $this->redirect('checkout/confirm#show-registration');
        } else {
            $this->redirect('account#show-registration');
        }
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
            return ['brutto' => 0, 'netto' => 0];
        }
        $shippingcosts = $this->admin->sGetPremiumShippingcosts($country);

        return empty($shippingcosts) ? ['brutto' => 0, 'netto' => 0] : $shippingcosts;
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
        $result = [];

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
                ', [$item['ordernumber']]);
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
                    ', [$resultVoucherTaxMode]);
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
        $inStock['quantity'] += $quantity;

        if (empty($inStock['articleID'])) {
            return Shopware()->Snippets()->getNamespace('frontend')->get('CheckoutArticleNotFound',
                'Product could not be found.', true);
        }
        if (!empty($inStock['laststock']) || !empty(Shopware()->Config()->InstockInfo)) {
            if ($inStock['instock'] <= 0 && !empty($inStock['laststock'])) {
                return Shopware()->Snippets()->getNamespace('frontend')->get('CheckoutArticleNoStock',
                    'Unfortunately we can not deliver the desired product in sufficient quantity', true);
            } elseif ($inStock['instock'] < $inStock['quantity']) {
                $result = 'Unfortunately we can not deliver the desired product in sufficient quantity. (#0 of #1 in stock).';
                $result = Shopware()->Snippets()->getNamespace('frontend')->get('CheckoutArticleLessStock', $result,
                    true);

                return str_replace(['#0', '#1'], [$inStock['instock'], $inStock['quantity']], $result);
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
        $row = Shopware()->Db()->fetchRow($sql, [
            $ordernumber,
            Shopware()->Session()->get('sessionId'),
        ]);

        return $row;
    }

    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string        $jwt            The JWT
     * @param string|array  $key            The key, or map of keys.
     *                                      If the algorithm used is asymmetric, this is the public key
     *
     * @return object The JWT's payload as a PHP object
     *
     * @throws UnexpectedValueException     Provided JWT was invalid
     *
     * @uses urlsafeB64Decode
     */
    public static function jwtDecode($jwt, $key)
    {
        $timestamp = time();
        if (empty($key)) {
            throw new InvalidArgumentException('Key may not be empty');
        }
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = json_decode(static::urlsafeB64Decode($headb64), false, 512, JSON_BIGINT_AS_STRING))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        if (null === $payload = json_decode(static::urlsafeB64Decode($bodyb64), false, 512, JSON_BIGINT_AS_STRING)) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        if (false === ($sig = static::urlsafeB64Decode($cryptob64))) {
            throw new UnexpectedValueException('Invalid signature encoding');
        }
        if (is_array($key) || $key instanceof \ArrayAccess) {
            if (isset($header->kid)) {
                if (!isset($key[$header->kid])) {
                    throw new UnexpectedValueException('"kid" invalid, unable to lookup correct key');
                }
                $key = $key[$header->kid];
            } else {
                throw new UnexpectedValueException('"kid" empty, unable to lookup correct key');
            }
        }
        // Check the signature
        if (!static::jwtVerify("$headb64.$bodyb64", $sig, $key)) {
            throw new UnexpectedValueException('Signature verification failed');
        }
        // Check if the nbf if it is defined. This is the time that the
        // token can actually be used. If it's not yet that time, abort.
        if (isset($payload->nbf) && $payload->nbf > ($timestamp + 100)) {
            throw new UnexpectedValueException(
                'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf)
            );
        }
        // Check that this token has been created before 'now'. This prevents
        // using tokens that have been created for later use (and haven't
        // correctly used the nbf claim).
        if (isset($payload->iat) && $payload->iat > ($timestamp + 100)) {
            throw new UnexpectedValueException(
                'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat)
            );
        }
        // Check if this token has expired.
        if (isset($payload->exp) && ($timestamp - 100) >= $payload->exp) {
            throw new UnexpectedValueException('Expired token');
        }
        return $payload;
    }

    /**
     * Verify a signature with the message, key and method. Not all methods
     * are symmetric, so we must have a separate verify and sign method.
     *
     * @param string            $msg        The original message (header and body)
     * @param string            $signature  The original signature
     * @param string|resource   $key        For HS*, a string key works. for RS*, must be a resource of an openssl public key
     * @param string            $alg        The algorithm
     *
     * @return bool
     *
     * @throws DomainException Invalid Algorithm or OpenSSL failure
     */
    private static function jwtVerify($msg, $signature, $key)
    {
        $hash = hash_hmac('SHA256', $msg, $key, true);
        if (function_exists('hash_equals')) {
            return hash_equals($signature, $hash);
        }
        $len = min(static::safeStrlen($signature), static::safeStrlen($hash));
        $status = 0;
        for ($i = 0; $i < $len; $i++) {
            $status |= (ord($signature[$i]) ^ ord($hash[$i]));
        }
        $status |= (static::safeStrlen($signature) ^ static::safeStrlen($hash));
        return ($status === 0);
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
