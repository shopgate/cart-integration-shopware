<?php

/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopgate\Components;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_Request_Request;
use Enlight_Controller_Response_Response;
use Exception;
use sAdmin;
use sBasket;
use Shopgate\Helpers\WebCheckout;
use Shopware\Components\DependencyInjection\Container;

class User
{
    /**
     * Reference to Shopware session object (Shopware()->Session)
     *
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

    /**
     * @var WebCheckout
     */
    protected $webCheckoutHelper;

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
     * Reference to Shopware container object (Shopware()->Container)
     *
     * @var Container
     */
    protected $container;

    public function __construct()
    {
        $this->webCheckoutHelper = new WebCheckout();
        $this->basket = Shopware()->Modules()->Basket();
        $this->admin = Shopware()->Modules()->Admin();
        $this->session = Shopware()->Session();
        $this->container = Shopware()->Container();
    }

    /**
     * @param Enlight_Controller_Request_Request   $request
     * @param Enlight_Controller_Response_Response $httpResponse
     */
    public function loginUser($request, $httpResponse)
    {
        $hash = $request->getPost('passwordMD5');
        $sessionId = $request->getPost('sessionId');

        if (isset($sessionId)) {
            /**
             * Set session id using both methods because standard shopware
             * login merges basket with session_id. Setting the session_id
             * requires resetting the session entirely since it can no longer
             * be changed once session_start() was called.
             *
             * All session variables are saved and replaced once the session
             * is recreated with the desired id.
             */
            $oldSession = array_merge([], $_SESSION);
            $this->webCheckoutHelper->startSessionWithId($sessionId);
            $_SESSION = array_merge([], $oldSession); // Replace old session variables

            $this->session->offsetSet('sessionId', $sessionId);
        }

        $this->basket->sGetBasket();

        $httpResponse->setHeader('Content-Type', 'application/json');

        if (isset($hash)) {
            $email = strtolower($request->getPost('email'));
            $user = $this->verifyUser($email, $hash);
            if (!empty($user['sErrorMessages'])) {
                $httpResponse->setHttpResponseCode(401);
                $httpResponse->setBody(json_encode($user));
                $httpResponse->sendResponse();
                $this->webCheckoutHelper->closeRequest();

                return;
            }
            $httpResponse->setHttpResponseCode(200);
            $httpResponse->setBody(
                json_encode(
                    [
                        'id' => $user['customernumber'],
                        'mail' => $user['email'],
                        'first_name' => $user['firstname'],
                        'last_name' => $user['lastname'],
                        'birthday' => $user['birthday'],
                        'customer_groups' => $user['customergroup'],
                        'session_id' => $user['sessionID'],
                    ]
                )
            );
            $httpResponse->sendResponse();
            $this->webCheckoutHelper->closeRequest();

            return;
        }
        $error = $this->admin->sLogin();

        if (!empty($error['sErrorMessages'])) {
            $httpResponse->setHttpResponseCode(401);
            $httpResponse->setBody(json_encode($error));
        } else {
            $user = $this->admin->sGetUserData();
            $user = $user['additional']['user'];

            $httpResponse->setHttpResponseCode(200);
            $httpResponse->setBody(
                json_encode(
                    [
                        'id' => $user['customernumber'],
                        'mail' => $user['email'],
                        'first_name' => $user['firstname'],
                        'last_name' => $user['lastname'],
                        'birthday' => $user['birthday'],
                        'customer_groups' => $user['customergroup'],
                        'session_id' => $user['sessionID'],
                    ]
                )
            );
        }

        $this->basket->sRefreshBasket();

        $httpResponse->sendResponse();
        $this->webCheckoutHelper->closeRequest();
    }

    /**
     * Custom get user action
     *
     * @param Enlight_Controller_Request_Request $request
     */
    public function getUser($request)
    {
        try {
            $token = $this->webCheckoutHelper->getTokenFromCall($request);
            $decoded = $this->webCheckoutHelper->getJWT($token);

            if (isset($decoded['error']) && $decoded['error']) {
                return $decoded;
            }

            $customerId = $decoded['customer_id'];

            $sql =
                ' SELECT id FROM s_user WHERE customernumber = ? AND active=1 AND (lockeduntil < now() OR lockeduntil IS NULL) ';
            $userId = Shopware()->Db()->fetchAll($sql, [$customerId]) ?: [];

            if (!\is_array($userId) || !$userId[0]['id']) {
                return [
                    'error' => true,
                    'id' => $userId,
                    'customerId' => $customerId,
                    'message' => 'query error',
                ];
            }

            if (\count($userId) > 1) {
                return [
                    'error' => true,
                    'id' => $userId,
                    'customerId' => $customerId,
                    'message' => 'multiple users found',
                ];
            }

            $user = Shopware()->Models()->find('Shopware\\Models\\Customer\\Customer', $userId[0]);

            return [
                'id' => $user->getNumber(),
                'mail' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'birthday' => $user->getBirthDay(),
                'customerGroups' => $user->getGroupKey(),
                'additional' => $user->getAdditional(),
            ];
        } catch (Exception $error) {
            return [
                'error' => true,
                'message' => $error->getMessage(),
            ];
        }
    }

    /**
     * Custom action to update user data
     *
     * @param Enlight_Controller_Request_Request $request
     *
     * @return array
     */
    public function updateUser($request, $httpResponse)
    {
        $response = [
            'success' => true,
            'message' => '',
        ];

        try {
            $params = $this->webCheckoutHelper->getJsonParams($request, $httpResponse);
            $decoded = $this->webCheckoutHelper->getJWT($params['token']);

            if (isset($decoded['error']) && $decoded['error']) {
                $response['success'] = false;
                $response['message'] = $decoded['message'];

                return $response;
            }

            $customer = $this->webCheckoutHelper->getCustomer($decoded['customer_id']);
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

        return $response;
    }

    /**
     * Custom action to update user email
     *
     * @param Enlight_Controller_Request_Request $request
     *
     * @return array
     */
    public function updateUserEmail($request, $httpResponse)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];

        try {
            $params = $this->webCheckoutHelper->getJsonParams($request, $httpResponse);
            $decoded = $this->webCheckoutHelper->getJWT($params['token']);

            if (isset($decoded['error']) && $decoded['error']) {
                $response['message'] = $decoded['message'];

                return $response;
            }

            $customer = $this->webCheckoutHelper->getCustomer($decoded['customer_id']);

            $form = $this->createForm("Shopware\\Bundle\\AccountBundle\\Form\Account\\EmailUpdateFormType", $customer);
            $emailData = [
                'email' => $decoded['email'],
                'emailConfirmation' => $decoded['email'],
            ];
            $form->submit($emailData, false);

            if ($form->isValid()) {
                $customerService = Shopware()->Container()->get('shopware_account.customer_service');
                $customerService->update($customer);
                $response['success'] = true;
            } else {
                $errors = $form->getErrors(true);
                $string = '';
                foreach ($errors as $error) {
                    $string .= $error->getMessage() . "\n";
                }
                $response['message'] = $string;
            }
        } catch (Exception $error) {
            $response['message'] = $error->getMessage();
        }

        return $response;
    }

    /**
     * Custom action to update user password
     *
     * @param Enlight_Controller_Request_Request   $request
     * @param Enlight_Controller_Response_Response $httpResponse
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     *
     * @return array
     */
    public function updateUserPassword($request, $httpResponse)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];

        $params = $this->webCheckoutHelper->getJsonParams($request, $httpResponse);
        $decoded = $this->webCheckoutHelper->getJWT($params['token']);

        if (isset($decoded['error']) && $decoded['error']) {
            $response['message'] = $decoded['message'];

            return $response;
        }

        $customer = $this->webCheckoutHelper->getCustomer($decoded['customer_id']);

        Shopware()->Container()->get('session')->offsetSet('sUserPassword', $customer->getPassword());

        $form = $this->createForm("Shopware\\Bundle\\AccountBundle\\Form\Account\\PasswordUpdateFormType", $customer);
        $passwordData = [
            'password' => $decoded['password'],
            'passwordConfirmation' => $decoded['password'],
            'currentPassword' => $decoded['old_password'],
        ];
        $form->submit($passwordData);

        if ($form->isValid()) {
            $customerService = Shopware()->Container()->get('shopware_account.customer_service');
            $customerService->update($customer);
            $response['success'] = true;
        } else {
            $errors = $form->getErrors(true);
            $string = '';
            foreach ($errors as $error) {
                $string .= $error->getMessage() . "\n";
            }
            $response['message'] = $string;
        }

        return $response;
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string $type    The fully qualified class name of the form type
     * @param mixed  $data    The initial data for the form
     * @param array  $options Options for the form
     *
     * @throws Exception
     *
     * @return Form
     */
    protected function createForm($type, $data = null, array $options = [])
    {
        return $this->container->get('shopware.form.factory')->create($type, $data, $options);
    }

    /**
     * Verify if user credentials are valid
     *
     * @return array
     */
    private function verifyUser($email, $hash)
    {
        if (empty($email)) {
            $sErrorFlag['email'] = true;
        }
        if (empty($hash)) {
            $sErrorFlag['password'] = true;
        }

        $mainShop = Shopware()->Shop()->getMain() !== null ? Shopware()->Shop()->getMain() : Shopware()->Shop();
        $scopedRegistration = $mainShop->getCustomerScope();

        $addScopeSql = '';
        if ($scopedRegistration == true) {
            $addScopeSql = Shopware()->Db()->quoteInto(' AND subshopID = ? ', $mainShop->getId());
        }

        $preHashedSql = Shopware()->Db()->quoteInto(' AND password = ? ', $hash);

        $sql = '
                SELECT id, customergroup, password, encoder
                FROM s_user WHERE email = ? AND active=1
                AND (lockeduntil < now() OR lockeduntil IS NULL) '
            . $addScopeSql
            . $preHashedSql;

        $getUser = Shopware()->Db()->fetchRow($sql, [$email]) ?: [];

        if (!\count($getUser)) {
            $isValidLogin = false;
        } else {
            $encoderName = 'Prehashed';

            $plaintext = $hash;
            $password = $getUser['password'];

            $isValidLogin = Shopware()->PasswordEncoder()->isPasswordValid($plaintext, $password, $encoderName);
        }

        if (!$isValidLogin) {
            $sErrorMessages = [];
            $sErrorMessages['sErrorMessages'] = 'your account is invalid';

            return $sErrorMessages;
        }

        $userId = $getUser['id'];
        $sql = '
            SELECT * FROM s_user
            WHERE password = ? AND email = ? AND id = ?
            AND UNIX_TIMESTAMP(lastlogin) >= (UNIX_TIMESTAMP(now())-?)
        ';

        $user = Shopware()->Db()->fetchRow(
            $sql, [$hash, $email, $userId, 7200]
        );

        return $user;
    }
}
