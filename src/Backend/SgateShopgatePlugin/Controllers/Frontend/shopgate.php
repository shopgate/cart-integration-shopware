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
use Shopgate\Helpers\WebCheckout;

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
     * Id of current active shop
     *
     * @var int s_core_shops.id
     */
    public $subshopId;

    /**
<<<<<<< HEAD
=======
     * Shopware password encoder.
     * Injected over the class constructor
     *
     * @var \Shopware\Components\Password\Manager
     */
    private $passwordEncoder;

    /**
>>>>>>> d89587f8cea83606726a12ef90a24c22f796aaae
     * @var StoreFrontBundle\Service\ProductServiceInterface
     */
    private $productService;

    /**
     * @var StoreFrontBundle\Service\ContextServiceInterface
     */
    private $contextService;

    /**
     * @var \Shopgate\Components\Cart
     */
    private $webCheckoutCartService;

    /**
     * @var Shopgate\Components\User
     */
    private $webCheckoutUserService;

    /**
     * @var WebCheckout
     */
    private $webCheckoutHelper;

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
<<<<<<< HEAD
=======
        $this->passwordEncoder = Shopware()->PasswordEncoder();
>>>>>>> d89587f8cea83606726a12ef90a24c22f796aaae
        $this->webCheckoutCartService = new Shopgate\Components\Cart();
        $this->webCheckoutUserService = new Shopgate\Components\User();
        $this->webCheckoutHelper = new WebCheckout();

        $this->contextService = $container->get('shopware_storefront.context_service');
        $this->productService = $container->get('shopware_storefront.product_service');

        $this->subshopId = $this->contextService->getShopContext()->getShop()->getParentId();
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
            'address',
            'favorites'
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
        $this->session->offsetSet('sgWebView', true);

        if ($this->webCheckoutHelper->loginAppUser($token, $this->Request())) {
            $this->session->offsetSet('sgAccountView', true);
            $this->redirect('account');
        } else {
            $this->redirect('shopgate/error');
        }
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
        $this->session->offsetSet('sgWebView', true);

        if ($this->webCheckoutHelper->loginAppUser($token, $this->Request())) {
            $this->session->offsetSet('sgAccountView', true);
            $this->redirect('account/orders');
        } else {
            $this->redirect('shopgate/error');
        }
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
        $this->session->offsetSet('sgWebView', true);

        if ($this->webCheckoutHelper->loginAppUser($token, $this->Request())) {
            $this->redirect('checkout/cart');
        } else {
            $this->redirect('shopgate/error');
        }
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
        $this->session->offsetSet('sgWebView', true);

        if ($this->webCheckoutHelper->loginAppUser($token, $this->Request())) {
            $this->redirect('checkout/confirm');
        } else {
            $this->redirect('shopgate/error');
        }
    }

    /**
     * Custom function to add coupon to cart
     */
    public function addCouponsCodeAction()
    {
        if (!$this->Request()->isPost()) {
            return;
        }

        $this->webCheckoutCartService->addCouponsCode($this->Request(), $this->Response(), $this->View());
    }

    /**
     * Custom function to get the cart
     */
    public function getCartAction()
    {
        $this->webCheckoutCartService->getCart($this->Request(), $this->Response(), $this->View());
    }

    /**
     * Custom function to add items to cart
     */
    public function addToCartAction()
    {
        if (!$this->Request()->isPost()) {
            return;
        }

        $this->webCheckoutCartService->addCartItems($this->Request(), $this->Response());
    }

    /**
     * Custom function to delete article from cart
     */
    public function deleteCartItemAction()
    {
        if (!$this->Request()->isDelete()) {
            return;
        }

        $this->webCheckoutCartService->deleteCartItem($this->Request(), $this->Response());
    }

    /**
     * Custom function to update a cart item
     */
    public function updateCartItemAction()
    {
        if (!$this->Request()->isPut()) {
            return;
        }

        $this->webCheckoutCartService->updateCartItem($this->Request(), $this->Response());
    }

    /**
     * Controller action for login error message in frontend
     */
    public function errorAction()
    {
        Shopware()->Events()->notify(
            'Shopgate_Frontend_Custom_Event',
            array(
                'request' => $this->Request(),
                'subject' => $this
            )
        );
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

        $this->webCheckoutUserService->loginUser($this->Request(), $this->Response());
    }

    /**
     * Custom get user action
     */
    public function getUserAction()
    {
        $response = $this->webCheckoutUserService->getUser($this->Request());

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($response));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom action to update user data
     */
    public function updateUserAction()
    {
        $response = $this->webCheckoutUserService->updateUser($this->Request());

        $this->Response()->setHeader('Content-Type', 'application/json');
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

        $response = $this->webCheckoutUserService->updateUserEmail($this->Request());

        $this->Response()->setHeader('Content-Type', 'application/json');
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

        $response = $this->webCheckoutUserService->updateUserPassword($this->Request());

        $this->Response()->setHeader('Content-Type', 'application/json');
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
     * Custom address action for address requests handling
     */
    public function addressAction()
    {
        $address = new Shopgate\Components\Address();

        if ($this->Request()->isPost() || $this->Request()->isPut()) {
            $response = $address->addAddressAction($this->Request());
        } elseif ($this->Request()->isDelete()) {
            $response = $address->deleteAddressAction($this->Request());
        } else {
            $response = $address->getAddressesAction($this->Request());
        }

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($response));
        $this->Response()->sendResponse();
        exit();
    }

    /**
     * Custom favorites action for favorites request handling
     */
    public function favoritesAction()
    {
        $favoritesService = new Shopgate\Components\Favorites();

        if ($this->Request()->isPost()) {
            $response = $favoritesService->addToFavoriteList($this->Request());
        } elseif ($this->Request()->isDelete()) {
            $response = $favoritesService->deleteFromFavoriteList($this->Request());
        } else {
            $response = $favoritesService->getFavorites($this->Request());
        }

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($response));
        $this->Response()->sendResponse();
        exit();
    }
}
