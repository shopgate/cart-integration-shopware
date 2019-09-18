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

namespace Shopgate\Components;

use Shopgate\Helpers\WebCheckout;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;

class Cart
{
    /**
     * @var ContextServiceInterface
     */
    private $contextService;

    /**
     * Reference to sBasket object (core/class/sBasket.php)
     *
     * @var sBasket
     */
    protected $basket;

    /**
     * Reference to sAdmin object (core/class/sAdmin.php)
     *
     * @var sAdmin
     */
    protected $admin;

    /**
     * @var WebCheckout
     */
    protected $webCheckoutHelper;

    /**
     * Reference to Shopware session object (Shopware()->Session)
     *
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

    /**
     * Cart constructor.
     */
    public function __construct()
    {
        $this->webCheckoutHelper = new WebCheckout();
        $this->session           = Shopware()->Session();
        $this->basket            = Shopware()->Modules()->Basket();
        $this->admin             = Shopware()->Modules()->Admin();
        $this->contextService    = Shopware()->Container()->get('shopware_storefront.context_service');
    }

    /**
     * Custom function to get the cart
     *
     * @param Enlight_Controller_Request_Request       $request
     * @param Enlight_Controller_Response_ResponseHttp $httpResponse
     * @param Enlight_View_Default                     $view
     */
    public function getCart($request, $httpResponse, $view)
    {
        $sessionId         = $request->getCookie('sg_session');
        $customerId        = $request->getCookie('customer_id');
        $promotionVouchers = json_decode($request->getCookie('sg_promotion'), true);

        $this->session->offsetSet('sessionId', $sessionId);
        session_id($sessionId);

        if (isset($promotionVouchers)) {
            $this->session->offsetSet('promotionVouchers', $promotionVouchers);
        }

        if (!empty($customerId) && $customerId !== "null") {
            $customer = $this->webCheckoutHelper->getCustomer($customerId);
            $this->session->offsetSet('sUserMail', $customer->getEmail());
            $this->session->offsetSet('sUserPassword', $customer->getPassword());
            $this->session->offsetSet('sUserId', $customer->getId());
            $this->admin->sCheckUser();
        }

        $basket = Shopware()->Modules()->Basket()->sGetBasket();

        if (!isset($basket)) {
            $httpResponse->setHttpResponseCode(401);
            $httpResponse->setHeader('Content-Type', 'application/json');
            $httpResponse->setBody(json_encode($basket));
            $httpResponse->sendResponse();
            exit();
        }

        $shippingcosts = $this->getShippingCosts($request, $view);

        $currency = Shopware()->Shop()->getCurrency();

        // Below code comes from the getBasket function in the Checkout Controller
        $basket['priceGroup']      = $this->session->offsetGet('sUserGroup');
        $basket['sCurrencyId']     = $currency->getId();
        $basket['sCurrencyName']   = $currency->getCurrency();
        $basket['sCurrencyFactor'] = $currency->getFactor();
        $basket['sCurrencySymbol'] = $currency->getSymbol();

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
        if (!Shopware()->Modules()->System()->sUSERGROUPDATA['tax']
            && Shopware()->Modules()->System()->sUSERGROUPDATA['id']
        ) {
            $basket['sTaxRates'] = $this->getTaxRates($basket);

            $basket['sShippingcosts'] = $shippingcosts['netto'];
            $basket['sAmount']        = round($basket['AmountNetNumeric'], 2);
            $basket['sAmountTax']     = round($basket['AmountWithTaxNumeric'] - $basket['AmountNetNumeric'], 2);
            $basket['sAmountWithTax'] = round($basket['AmountWithTaxNumeric'], 2);
        } else {
            $basket['sTaxRates'] = $this->getTaxRates($basket);

            $basket['sShippingcosts'] = $shippingcosts['brutto'];
            $basket['sAmount']        = $basket['AmountNumeric'];

            $basket['sAmountTax'] = round($basket['AmountNumeric'] - $basket['AmountNetNumeric'], 2);
        }

        $httpResponse->setHttpResponseCode(200);
        $httpResponse->setHeader('Content-Type', 'application/json');
        $httpResponse->setBody(json_encode($basket));
        $httpResponse->sendResponse();
        exit();
    }

    /**
     * Custom function to add items to cart
     *
     * @param Enlight_Controller_Request_Request       $request
     * @param Enlight_Controller_Response_ResponseHttp $httpResponse
     */
    public function addCartItems($request, $httpResponse)
    {
        // @Below code: Standard Shopware function to get JSON data from the POST array doesn't work
        $params = $this->webCheckoutHelper->getJsonParams($request);

        $articles          = $params['articles'];
        $sessionId         = $params['sessionId'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);

        if (!isset($articles)) {
            $httpResponse->setHttpResponseCode(401);
            $httpResponse->setBody(
                json_encode(array('message' => 'The request doesn\'t contain an \'articles\' parameter!'))
            );
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
            $httpResponse->setHttpResponseCode(401);
            $httpResponse->setBody(json_encode($response));
        } else {
            $sessionId = $this->session->get('sessionId');
            $httpResponse->setHttpResponseCode(201);
            $httpResponse->setBody(json_encode(array('sessionId' => $sessionId)));
        }
        $httpResponse->setHeader('Content-Type', 'application/json');
        $httpResponse->sendResponse();
        exit();
    }

    /**
     * Custom function to update a cart item
     *
     * @param Enlight_Controller_Request_Request       $request
     * @param Enlight_Controller_Response_ResponseHttp $httpResponse
     */
    public function updateCartItem($request, $httpResponse)
    {
        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->webCheckoutHelper->getJsonParams($request);

        $basketId          = $params['basketId'];
        $quantity          = $params['quantity'];
        $sessionId         = $params['sessionId'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if (isset($promotionVouchers)) {
            $this->session->offsetSet('promotionVouchers', $promotionVouchers);
        }

        $httpResponse->setHeader('Content-Type', 'application/json');
        if ($error = $this->verifyItemStock($basketId, $quantity)) {
            $httpResponse->setHttpResponseCode(401);
            $httpResponse->setBody(
                json_encode(
                    array(
                        'error'  => true,
                        'reason' => $error
                    )
                )
            );
        } else {
            $response = $this->basket->sUpdateArticle($basketId, $quantity);
            $httpResponse->setBody(json_encode($response));
        }

        $httpResponse->sendResponse();
        exit();
    }

    /**
     * Custom function to delete item from cart
     *
     * @param Enlight_Controller_Request_Request       $request
     * @param Enlight_Controller_Response_ResponseHttp $httpResponse
     */
    public function deleteCartItem($request, $httpResponse)
    {
        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->webCheckoutHelper->getJsonParams($request);

        $articleId         = $params['articleId'];
        $sessionId         = $params['sessionId'];
        $voucher           = $params['voucher'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);

        $response['oldPromotionVouchers'] = $promotionVouchers;

        if (isset($sessionId)) {
            $this->session->offsetSet('sessionId', $sessionId);
            session_id($sessionId);
        }

        if (isset($promotionVouchers) && $voucher) {
            $sql         = 'SELECT DISTINCT `ordernumber` FROM `s_order_basket` WHERE id=?';
            $orderNumber = Shopware()->Db()->fetchCol($sql, array($articleId));

            $sql  = 'SELECT 1 FROM `s_plugin_promotion` LIMIT 1';
            $test = Shopware()->Db()->fetchCol($sql);
            if ($test) {
                $sql       = 'SELECT DISTINCT `id` FROM `s_plugin_promotion` WHERE number=?';
                $voucherId = Shopware()->Db()->fetchCol($sql, $orderNumber);

                $response['voucherId'] = $voucherId;

                if ($voucherId) {
                    $this->session->offsetSet('promotionVouchers', $promotionVouchers[$voucherId]);
                    $response['newPromotionVouchers'] = $promotionVouchers[$voucherId];
                }
            }
        }
        $response['deleteArticle']     = $this->basket->sDeleteArticle($articleId);
        $response['promotionVouchers'] = json_encode($this->session->get('promotionVouchers'));

        $httpResponse->setHeader('Content-Type', 'application/json');
        $httpResponse->setBody(json_encode($response));
        $httpResponse->sendResponse();
        exit();
    }

    /**
     * Custom function to add coupon to cart
     *
     * @param Enlight_Controller_Request_Request       $request
     * @param Enlight_Controller_Response_ResponseHttp $httpResponse
     * @param Enlight_View_Default                     $view
     */
    public function addCouponsCode($request, $httpResponse, $view)
    {
        // @Below code: Standard Shopware function to get JSON data from the POST array don't work
        $params = $this->webCheckoutHelper->getJsonParams($request);

        $code              = $params['couponCode'];
        $sessionId         = $params['sessionId'];
        $promotionVouchers = json_decode($params['promotionVouchers'], true);
        $customerId        = $params['customerId'];

        if (isset($customerId)) {
            $sql    = 'SELECT DISTINCT `id` FROM `s_user` WHERE customernumber=?';
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

        $httpResponse->setHeader('Content-Type', 'application/json');

        $voucher                = $this->basket->sAddVoucher($code);
        $response['addVoucher'] = $voucher;

        if ($voucher) {
            $response['promotionVouchers'] = json_encode($this->session->get('promotionVouchers'));

            $httpResponse->setBody(html_entity_decode(json_encode($response)));
            $httpResponse->sendResponse();
            exit();
        }

        $this->basket->sGetBasket();

        $promotionVariables    = $view->getAssign();
        $voucherPromotionId    = $promotionVariables['voucherPromotionId'];
        $promotionUsedTooOften = $promotionVariables['promotionsUsedTooOften'];
        $promotions            = $this->session->get('promotionVouchers');

        foreach ($promotionUsedTooOften as $promotionUsed) {
            if ($promotionUsed->id == $voucherPromotionId) {
                $text = Shopware()->Snippets()->getNamespace('frontend/swag_promotion/main')->get('usedPromotions');
                $text = str_replace('{$promotionUsedTooOften->name}', $promotionUsed->name, $text);
                $text = str_replace('{$promotionUsedTooOften->maxUsage}', $promotionUsed->maxUsage, $text);

                $response['addVoucher']['sErrorFlag']        = true;
                $response['addVoucher']['sErrorMessages'][0] = $text;

                foreach ($promotions as $key => $promotion) {
                    if ($promotion['promotionId'] == $voucherPromotionId) {
                        unset($promotions[$key]);
                    }
                }
            }
        }

        $response['promotionVouchers'] = json_encode($promotions);

        $httpResponse->setHeader('Content-Type', 'application/json');
        $httpResponse->setBody(html_entity_decode(json_encode($response)));
        $httpResponse->sendResponse();
        exit();
    }

    /**
     * Get complete user-data as an array to use in view
     *
     * @return array
     */
    private function getUserData()
    {
        $system   = Shopware()->System();
        $userData = $this->admin->sGetUserData();
        if (!empty($userData['additional']['countryShipping'])) {
            $system->sUSERGROUPDATA = Shopware()->Db()->fetchRow('
                SELECT * FROM s_core_customergroups
                WHERE groupkey = ?
            ', array($system->sUSERGROUP)
            );

            $taxFree = $this->isTaxFreeDelivery($userData);
            $this->session->offsetSet('taxFree', $taxFree);

            if ($taxFree) {
                $system->sUSERGROUPDATA['tax']           = 0;
                $system->sCONFIG['sARTICLESOUTPUTNETTO'] = 1; //Old template
                Shopware()->Session()->sUserGroupData    = $system->sUSERGROUPDATA;
                $userData['additional']['charge_vat']    = false;
                $userData['additional']['show_net']      = false;
                Shopware()->Session()->sOutputNet        = true;
            } else {
                $userData['additional']['charge_vat'] = true;
                $userData['additional']['show_net']   = !empty($system->sUSERGROUPDATA['tax']);
                Shopware()->Session()->sOutputNet     = empty($system->sUSERGROUPDATA['tax']);
            }
        }

        return $userData;
    }

    /**
     * Adds an array of articles to the cart based on an array of article IDs
     *
     * @param array  $articles
     * @param string $sessionId
     *
     * @return array
     */
    private function addArticlesToCart($articles, $sessionId)
    {
        $response = array(); // Contains only errors

        foreach ($articles as $article) {
            $articleId   = trim($article['product_id']);
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

                $quantity = $article['quantity'];

                if ($basketProduct = $statement->fetch()) {
                    $quantity += $basketProduct['quantity'];
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
            return Shopware()->Snippets()->getNamespace('frontend')->get(
                'CheckoutSelectVariant', 'Please select an option to place the required product in the cart', true
            );
        }

        $quantity = max(1, (int)$quantity);
        $inStock  = $this->getAvailableStock($orderNumber);

        $inStock['quantity'] = $quantity;

        if (empty($inStock['articleID'])) {
            return Shopware()->Snippets()->getNamespace('frontend')->get(
                'CheckoutArticleNotFound', 'Product could not be found.', true
            );
        }
        if (!empty($inStock['laststock']) || !empty(Shopware()->Config()->InstockInfo)) {
            if ((int)$inStock['instock'] <= 0 && !empty($inStock['laststock'])) {
                return Shopware()->Snippets()->getNamespace('frontend')->get(
                    'CheckoutArticleNoStock',
                    'Unfortunately we can not deliver the desired product in sufficient quantity',
                    true
                );
            } elseif ((int)$inStock['instock'] < (int)$inStock['quantity']) {
                $result =
                    'Unfortunately we can not deliver the desired product in sufficient quantity. (#0 of #1 in stock).';
                $result = Shopware()->Snippets()->getNamespace('frontend')->get(
                    'CheckoutArticleLessStock', $result, true
                );

                return str_replace(array('#0', '#1'), array($inStock['instock'], $inStock['quantity']), $result);
            }
        }

        return null;
    }

    /**
     * Get shipping costs as an array (brutto / netto) depending on selected country / payment
     *
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_View_Default               $view
     *
     * @return array
     */
    private function getShippingCosts($request, $view)
    {
        $country = $this->getSelectedCountry($view);
        $payment = $this->getSelectedPayment($request, $view);
        if (empty($country) || empty($payment)) {
            return array('brutto' => 0, 'netto' => 0);
        }

        $this->session['sState'] = $view->sUserData['additional']['stateShipping']['id']
            ? (int)$view->sUserData['additional']['stateShipping']['id']
            : null;
        $dispatches = $this->admin->sGetPremiumDispatches($country['id'], null, $this->session['sState']);
        if (empty($dispatches)) {
            unset($this->session['sDispatch']);
        } else {
            $dispatch                   = reset($dispatches);
            $this->session['sDispatch'] = (int)$dispatch['id'];
        }

        $shippingcosts = $this->admin->sGetPremiumShippingcosts($country);

        return empty($shippingcosts) ? array('brutto' => 0, 'netto' => 0) : $shippingcosts;
    }

    /**
     * Get current selected country - if no country is selected, choose first one from list
     * of available countries
     *
     * @param Enlight_View_Default $view
     *
     * @return array with country information
     */
    private function getSelectedCountry($view)
    {
        if (!empty($view->sUserData['additional']['countryShipping'])) {
            $this->session['sCountry'] = (int)$view->sUserData['additional']['countryShipping']['id'];
            $this->session['sArea']    = (int)$view->sUserData['additional']['countryShipping']['areaID'];

            return $view->sUserData['additional']['countryShipping'];
        } else {
            $countries = $this->getCountryList();
            if (empty($countries)) {
                unset($this->session['sCountry']);

                return false;
            }
            $country                   = reset($countries);
            $this->session['sCountry'] = (int)$country['id'];
            $this->session['sArea']    = (int)$country['areaID'];

            return $country;
        }
    }

    /**
     * Get selected payment or do payment mean selection automatically
     *
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_View_Default               $view
     *
     * @return array
     */
    private function getSelectedPayment($request, $view)
    {
        $paymentMethods = $this->getPayments();

        if (!empty($view->sUserData['additional']['payment'])) {
            $payment = $view->sUserData['additional']['payment'];
        } elseif (!empty($this->session['sPaymentID'])) {
            $payment = $this->admin->sGetPaymentMeanById($this->session['sPaymentID'], $view->sUserData);
        }

        if ($payment && !$this->checkPaymentAvailability($payment, $paymentMethods)) {
            $payment = null;
        }

        $paymentClass = $this->admin->sInitiatePaymentClass($payment);
        if ($payment && $paymentClass instanceof \ShopwarePlugin\PaymentMethods\Components\BasePaymentMethod) {
            $data                  = $paymentClass->getCurrentPaymentDataAsArray(Shopware()->Session()->sUserId);
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

        $this->session['sPaymentID'] = (int)$payment['id'];
        $request->setPost('sPayment', (int)$payment['id']);
        $this->admin->sUpdatePayment();

        return $payment;
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
            $basket['sShippingcostsTax'] = number_format((float)$basket['sShippingcostsTax'], 2);

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
                ', array($item['ordernumber'])
                );
                // Old behaviour
                if (empty($resultVoucherTaxMode) || $resultVoucherTaxMode === 'default') {
                    $tax = Shopware()->Config()->get('sVOUCHERTAX');
                } elseif ($resultVoucherTaxMode === 'auto') {
                    // Automatically determinate tax
                    $tax = $this->basket->getMaxTax();
                } elseif ($resultVoucherTaxMode === 'none') {
                    // No tax
                    $tax = '0';
                } elseif ((int)$resultVoucherTaxMode) {
                    // Fix defined tax
                    $tax = Shopware()->Db()->fetchOne(
                        'SELECT tax FROM s_core_tax WHERE id = ?',
                        array($resultVoucherTaxMode)
                    );
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

            $taxKey = number_format((float)$item['tax_rate'], 2);

            $result[$taxKey] += str_replace(',', '.', $item['tax']);
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * Get current stock from a certain product defined by $ordernumber
     * Support for multidimensional variants
     *
     * @param string $ordernumber
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
        $row = Shopware()->Db()->fetchRow(
            $sql,
            array(
                $ordernumber,
                Shopware()->Session()->get('sessionId'),
            )
        );

        return $row;
    }

    /**
     * Verify if item quantity is in stock
     *
     * @param $basketId
     * @param $quantity
     *
     * @return null|string
     */
    private function verifyItemStock($basketId, $quantity)
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
     * Get all countries from database via sAdmin object
     *
     * @return array list of countries
     */
    private function getCountryList()
    {
        return $this->admin->sGetCountryList();
    }

    /**
     * Returns all available payment methods from sAdmin object
     *
     * @return array list of payment methods
     */
    private function getPayments()
    {
        return $this->admin->sGetPaymentMeans();
    }
}
