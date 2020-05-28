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

use Firebase\JWT\JWT;
use Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config;

class WebCheckout
{
    /**
     * Login app user from JWT token
     *
     * @param                                    $token
     * @param Enlight_Controller_Request_Request $request
     *
     * @return bool
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

            $sql = ' SELECT password, email FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';
            $user = Shopware()->Db()->fetchAll($sql, array($customerId)) ? : array();

            if (count($user) > 1) {
                return false;
            }

            $request->setPost('email', $user[0]["email"]);
            $request->setPost('passwordMD5', $user[0]["password"]);

            $checkUser = Shopware()->Modules()->Admin()->sLogin(true);

            if (isset($checkUser['sErrorFlag'])) {
                throw new Exception($checkUser['sErrorMessages'][0], 400);
            }

            Shopware()->Modules()->Basket()->sRefreshBasket();
            Shopware()->Modules()->Basket()->clearBasket();

            if (!empty($basket['content'])) {
                foreach ($basket['content'] as $basketItem) {
                    Shopware()->Modules()->Basket()->sAddArticle($basketItem['ordernumber'], $basketItem['quantity']);
                }

                if (!empty($voucher)) {
                    Shopware()->Modules()->Basket()->sAddVoucher($voucher['code']);
                }
            }

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
     * @return mixed
     */
    public function getJsonParams($request)
    {
        $header = $request->getHeader('Content-Type');

        if ($header !== 'application/json') {
            $this->Response()->setHttpResponseCode(404);
            $this->Response()->sendResponse();
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
     * @param $customerId
     *
     * @return Shopware\\Models\\Customer\\Customer $customer
     */
    public function getCustomer($customerId)
    {
        $sql = ' SELECT id FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';

        $userId = Shopware()->Db()->fetchRow($sql, array($customerId)) ? : array();
        Shopware()->Session()->offsetSet('sUserId', $userId['id']);

        return Shopware()->Models()->find("Shopware\\Models\\Customer\\Customer", $userId['id']);
    }
}
