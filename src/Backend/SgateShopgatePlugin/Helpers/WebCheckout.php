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

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enlight_Controller_Request_Request;
use Enlight_Controller_Response_Response;
use Enlight_Event_Exception;
use Enlight_Exception;
use Exception;
use Firebase\JWT\JWT;
use Shopware\Models\Customer\Customer;
use Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config;
use Zend_Db_Adapter_Exception;

class WebCheckout
{
    /**
     * Login app user from JWT token
     *
     * @param                                    $token
     * @param Enlight_Controller_Request_Request $request
     *
     * @return bool
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws Exception
     */
    public function loginAppUser($token, $request)
    {
        $basket  = Shopware()->Modules()->Basket()->sGetBasket();
        $voucher = $this->getVoucher();

        if (isset($token)) {
            $key               = trim($this->getConfig()->getApikey());
            JWT::$leeway       = 300;
            $decoded           = JWT::decode($token, $key, array('HS256'));
            $decoded           = json_decode(json_encode($decoded), true);
            $customerId        = $decoded['customer_id'];
            $promotionVouchers = json_decode($decoded['promotion_vouchers'], true);

            if (isset($promotionVouchers)) {
                Shopware()->Session()->offsetSet('promotionVouchers', $promotionVouchers);
            }

            $sql = ' SELECT id, password, email, password_change_date FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';
            $user = Shopware()->Db()->fetchAll($sql, array($customerId)) ?: array();

            if (count($user) > 1) {
                return false;
            }

            /**
             * Anti-CAPTCHA:
             *
             * During initialization, the system sometimes sets the Bot flag
             * in the session depending on the user agent. If the flag is set,
             * the session is scrapped and certain features, like adding
             * articles to the cart, are disabled. It's not always possible to
             * trick the system by spoofind the user agent so it's necessary
             * to explicitly disable the flag here or risk the cart items not
             * being set, resulting in a cleared cart upon login.
             *
             * @see ./Shopware/Plugins/Default/Core/System/Bootstrap.php:102 @onInitResourceSystem
             * @see ./Shopware/Core/sBasket.php:1804 @sAddArticle
             */
            if (Shopware()->Session()->Bot) {
                Shopware()->Session()->Bot = false;
            }

            $request->setPost('email', $user[0]["email"]);
            $request->setPost('passwordMD5', $user[0]["password"]);

            $checkUser = Shopware()->Modules()->Admin()->sLogin(true);

            if (isset($checkUser['sErrorFlag'])) {
                throw new Exception($checkUser['sErrorMessages'][0], 400);
            }
            Shopware()->Session()->offsetSet('sUserId', $user[0]['id']);
            Shopware()->Session()->offsetSet('sUserPasswordChangeDate', $user[0]['password_change_date']);

            return true;
        }

        return false;
    }

    /**
     * Returns the current basket voucher or false
     *
     * @return array|false
     */
    public function getVoucher()
    {
        $voucher = Shopware()->Db()->fetchRow(
            'SELECT id basketID, ordernumber, articleID as voucherID
                FROM s_order_basket
                WHERE modus = 2 AND sessionID = ?',
            array(Shopware()->Session()->get('sessionId'))
        );
        if (!empty($voucher)) {
            $voucher['code'] = Shopware()->Db()->fetchOne(
                'SELECT vouchercode FROM s_emarketing_vouchers WHERE ordercode = ?',
                array($voucher['ordernumber'])
            );
            if (empty($voucher['code'])) {
                $voucher['code'] = Shopware()->Db()->fetchOne(
                    'SELECT code FROM s_emarketing_voucher_codes WHERE id = ?',
                    array($voucher['voucherID'])
                );
            }
        }

        return $voucher;
    }

    /**
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_Controller_Response_Response $response
     * @return mixed
     * @throws Exception
     */
    public function getJsonParams($request, $response)
    {
        $header = $request->getHeader('Content-Type');

        if ($header !== 'application/json') {
            $response->setHttpResponseCode(404);
            $response->sendResponse();
            exit();
        }

        $content = trim(file_get_contents("php://input"));

        return json_decode($content, true);
    }

    /**
     * @param $token
     *
     * @return array
     */
    public function getJWT($token)
    {
        try {
            $key         = trim($this->getConfig()->getApikey());
            JWT::$leeway = 300;
            $decoded     = JWT::decode($token, $key, array('HS256'));

            return json_decode(json_encode($decoded), true);
        } catch (Exception $error) {
            return array(
                'error'   => true,
                'message' => $error->getMessage()
            );
        }
    }


    /**
     * @return Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config
     */
    public function getConfig()
    {
        static $config = null;

        if (!$config) {
            $config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        }

        return $config;
    }

    /**
     * @param string $customerNumber
     *
     * @return Customer $customer
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function getCustomer($customerNumber)
    {
        $customerId = $this->getCustomerId($customerNumber);

        return Shopware()->Models()->find("Shopware\\Models\\Customer\\Customer", $customerId);
    }

    /**
     * @param $customerNumber
     * @return int
     */
    public function getCustomerId($customerNumber)
    {
        $sql = ' SELECT id, password_change_date FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';

        $user = Shopware()->Db()->fetchRow($sql, array($customerNumber)) ?: array();
        Shopware()->Session()->offsetSet('sUserId', $user['id']);
        Shopware()->Session()->offsetSet('sUserPasswordChangeDate', $user['password_change_date']);

        return $user['id'];
    }

    /**
     * @param $sessionId
     */
    public function startSessionWithId($sessionId)
    {
        if ($this->getConfig()->assertMinimumVersion('5.7.0')) {
            Shopware()->Session()->save();
            Shopware()->Session()->setId($sessionId);
            Shopware()->Session()->start();
            Shopware()->Session()->offsetSet('sessionId', $sessionId);

            return;
        }

        session_commit();
        session_id($sessionId);
        session_start(array(
            'sessionId' => $sessionId
        ));
        Shopware()->Session()->offsetSet('sessionId', $sessionId);
    }

    public function getSessionId()
    {
        if ($this->getConfig()->assertMinimumVersion('5.7.0')) {
            return Shopware()->Session()->getId();
        }

        return session_id();
    }
}
