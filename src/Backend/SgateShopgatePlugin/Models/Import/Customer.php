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

/**
 * Class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Import_Customer
 */
class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Import_Customer
{
    const MALE   = "mr";
    const FEMALE = "ms";
    const NOT_DEFINED = 'not_defined';
    const GENDER_SALUTATION_MAP = [
        ShopgateCustomer::MALE => self::MALE,
        ShopgateCustomer::FEMALE => self::FEMALE,
        ShopgateCustomer::DIVERSE => self::NOT_DEFINED
    ];
    const DEFAULT_BIRTHDATE    = '';
    const DEFAULT_PHONE_NUMBER = '0-000-000-0000';

    /** @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config */
    protected $config;

    /** @var Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Import_Customer_Validator */
    protected $validator;

    public function __construct($config = null)
    {
        if ($config instanceof Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config) {
            $this->config = $config;
        } else {
            $this->config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        }

        $this->validator =
            new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Import_Customer_Validator($this->config);
    }

    /**
     * @param string           $email
     * @param string           $pass
     * @param ShopgateCustomer $customer
     * @param bool             $createGuest
     * @param bool             $disableDbTransaction
     *
     * @return int or null
     * @throws ShopgateLibraryException
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function createNewCustomer(
        $email,
        $pass,
        $customer,
        $createGuest = false,
        $disableDbTransaction = false
    ) {
        return $this->config->assertMinimumVersion('5.2.0')
            ? $this->createNewCustomerNew($email, $pass, $customer)
            : $this->createNewCustomerOld($email, $pass, $customer, $createGuest, $disableDbTransaction);
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return int|null
     */
    public function createGuestCustomer(ShopgateOrder $shopgateOrder)
    {
        // create a dummy ShopgateCustomer object to pretend as if register_customer was called
        $customer = new ShopgateCustomer();

        $customer->setPhone(
            $shopgateOrder->getPhone()
                ? $shopgateOrder->getPhone()
                : self::DEFAULT_PHONE_NUMBER
        );
        $customer->setBirthday(
            $shopgateOrder->getInvoiceAddress()->getBirthday()
                ? $shopgateOrder->getInvoiceAddress()->getBirthday()
                : self::DEFAULT_BIRTHDATE
        );
        $customer->setAddresses(
            array(
                $shopgateOrder->getInvoiceAddress(),
                $shopgateOrder->getDeliveryAddress(),
            )
        );

        $customer->setFirstName($shopgateOrder->getInvoiceAddress()->getFirstName());
        $customer->setLastName($shopgateOrder->getInvoiceAddress()->getLastName());
        $customer->setGender($shopgateOrder->getInvoiceAddress()->getGender());

        return $this->config->assertMinimumVersion('5.2.0')
            ? $this->createNewCustomerNew($shopgateOrder->getMail(), '', $customer, true)
            : $this->createNewCustomerOld($shopgateOrder->getMail(), '', $customer, true);
    }

    /**
     * @param array           $oldAddress
     * @param ShopgateAddress $newAddress
     */
    public function updateShippingAddress($oldAddress, $newAddress)
    {
        $country = $this->getCountryByIso($newAddress->getCountry());
        $state   = $this->getStateByIso($newAddress->getState());

        $salutation = self::NOT_DEFINED;
        if (!empty(self::GENDER_SALUTATION_MAP[$newAddress->getGender()])) {
            $salutation = self::GENDER_SALUTATION_MAP[$newAddress->getGender()];
        }

        $oldAddress['company']    = $newAddress->getCompany();
        $oldAddress['salutation'] = $salutation;
        $oldAddress['firstname']  = $newAddress->getFirstName();
        $oldAddress['lastname']   = $newAddress->getLastName();
        if (!$this->config->assertMinimumVersion('5.0.0')) {
            $oldAddress['street']       = $newAddress->getStreetName1();
            $oldAddress['streetnumber'] = $newAddress->getStreetNumber1()
                ? $newAddress->getStreetNumber1()
                : '.';
        } else {
            $oldAddress['street'] = $newAddress->getStreet1();
        }
        $oldAddress['zipcode']   = $newAddress->getZipcode();
        $oldAddress['city']      = $newAddress->getCity();
        $oldAddress['countryID'] = $country->getId();
        $oldAddress['stateID']   = $state
            ? $state->getId()
            : 0;

        $where = array(
            'userID = ?' => $oldAddress['userID'],
        );

        unset($oldAddress['id']);

        Shopware()->Db()->update('s_user_shippingaddress', $oldAddress, $where);
    }

    /**
     * @param string           $email
     * @param string           $pass
     * @param ShopgateCustomer $customer
     * @param bool             $createGuest
     * @param bool             $disableDbTransaction
     *
     * @return int or null
     * @throws ShopgateLibraryException
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function createNewCustomerOld(
        $email,
        $pass,
        $customer,
        $createGuest = false,
        $disableDbTransaction = false
    ) {
        /** @var ShopgateAddress $billingAddress */
        $billingAddress = null;

        /** @var ShopgateAddress $shippingAddress */
        $shippingAddress = null;

        /** @var ShopgateAddress $shopgateCustomerAddress */
        foreach ($customer->getAddresses() as $shopgateCustomerAddress) {
            if ($shopgateCustomerAddress->getIsInvoiceAddress()) {
                $billingAddress = $shopgateCustomerAddress;
            } else {
                $shippingAddress = $shopgateCustomerAddress;
            }

            // only one billing and one shipping address is supported by Shopware on registration
            if (!empty($billingAddress) && !empty($shippingAddress)) {
                break;
            }
        }

        // shipping address may be empty but not the billing address
        if (empty($billingAddress) && !empty($shippingAddress)) {
            // shift shipping address to billing address
            $billingAddress  = $shippingAddress;
            $shippingAddress = null;
        } elseif (!empty($billingAddress) && !empty($shippingAddress)) {
            // find out if the billing and shipping address is the same (compare complete array, except on field)
            $compareBillingArray  = $billingAddress->toArray();
            $compareShippingArray = $shippingAddress->toArray();
            // remove the array fields of which we know they are unrelated here and possibly different
            unset($compareBillingArray['id']);
            // which is always 1
            unset($compareBillingArray['is_invoice_address']);
            // which is always false
            unset($compareBillingArray['is_delivery_address']);
            unset($compareShippingArray['id']);
            // which is always false
            unset($compareShippingArray['is_invoice_address']);
            // which is always 1
            unset($compareShippingArray['is_delivery_address']);

            // reduce identical addresses to one billing address
            if ($compareBillingArray === $compareShippingArray) {
                $shippingAddress = null;
            }
        }

        // clear session data so that the account is created no matter how often the request is sent
        Shopware()->Session()->__set('sUserId', null);
        Shopware()->Session()->__set('sRegisterFinished', false);

        // create post data as if the user had logged in using the frontend
        $postData = $this->createRegistrationPostData(
            $email,
            $pass,
            $customer,
            $billingAddress,
            $shippingAddress,
            $createGuest
        );

        $userCustomFields     = array();
        $billingCustomFields  = array();
        $shippingCustomFields = array();
        foreach ($customer->getCustomFields() as $userCustomField) {
            if (isset($postData['personal'][$userCustomField->getInternalFieldName()])
                && $postData['personal'][$userCustomField->getInternalFieldName()] == $userCustomField->getValue()
            ) {
                continue;
            }
            $userCustomFields[] = $userCustomField;
        }
        foreach ($billingAddress->getCustomFields() as $billingCustomField) {
            if (isset($postData['billing'][$billingCustomField->getInternalFieldName()])
                && $postData['billing'][$billingCustomField->getInternalFieldName()] == $billingCustomField->getValue()
            ) {
                continue;
            }
            $billingCustomFields[] = $billingCustomField;
        }
        if (is_object($shippingAddress)) {
            foreach ($shippingAddress->getCustomFields() as $shippingCustomField) {
                if (isset($postData['shipping'][$shippingCustomField->getInternalFieldName()])
                    && $postData['billing'][$shippingCustomField->getInternalFieldName()]
                    == $shippingCustomField->getValue()
                ) {
                    continue;
                }
                $shippingCustomFields[] = $shippingCustomField;
            }
        }

        // process frontend-like validations
        $this->validator->registrationValidatePersonal($postData);
        $this->validator->registrationValidateBilling($postData);
        $this->validator->registrationValidateShipping($postData);

        try {
            // suspend auto-commit
            if (!$disableDbTransaction) {
                Shopware()->Models()->getConnection()->beginTransaction();
            }

            // save customer
            Shopware()->Modules()->Admin()->sSaveRegister();

            // commit
            if (!$disableDbTransaction) {
                Shopware()->Models()->getConnection()->commit();
            }
        } catch (\Shopware\Components\Api\Exception\ValidationException $e) {
            // rollback in case of exceptions
            if (!$disableDbTransaction) {
                Shopware()->Models()->getConnection()->rollback();
            }

            $errors = array();
            /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
            foreach ($e->getViolations() as $violation) {
                $errors[] = sprintf(
                    '%s: %s',
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                );
            }
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER, print_r($errors, true),
                true
            );
        } catch (Exception $e) {
            // rollback in case of exceptions
            if (!$disableDbTransaction) {
                Shopware()->Models()->getConnection()->rollback();
            }

            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $e->getMessage(), true);
        }

        $userId = Shopware()->Session()->__get('sUserId');

        if (empty($userId)) {
            return null;
        }

        $this->setUserCustomFields($userId, $userCustomFields, $billingCustomFields, $shippingCustomFields);

        return $userId;
    }

    /**
     * @param $userId
     * @param $userCustomFields
     * @param $billingCustomFields
     * @param $shippingCustomFields
     */
    protected function setUserCustomFields($userId, $userCustomFields, $billingCustomFields, $shippingCustomFields)
    {
        $fieldTexts = "";

        $fieldTexts .= (count($userCustomFields) > 0)
            ? "Zusatzfeld(er) zum Kunden:\n"
            : "";
        foreach ($userCustomFields as $userCustomField) {
            $fieldTexts .= $userCustomField->getLabel() . ":" . $userCustomField->getValue() . "\n";
        }

        $fieldTexts .= (count($shippingCustomFields) > 0)
            ? "Zusatzfeld(er) zu der Lieferadresse:\n"
            : "";
        foreach ($shippingCustomFields as $shippingCustomField) {
            $fieldTexts .= $shippingCustomField->getLabel() . ":" . $shippingCustomField->getValue() . "\n";
        }

        $fieldTexts .= (count($billingCustomFields) > 0)
            ? "Zusatzfeld(er) zu der Rechnungsadresse:\n"
            : "";
        foreach ($billingCustomFields as $billingCustomField) {
            $fieldTexts .= $billingCustomField->getLabel() . ":" . $billingCustomField->getValue() . "\n";
        }

        if (!empty($fieldTexts)) {
            $this->updateInternalUserComment($fieldTexts, $userId);
        }
    }

    /**
     * insert internal user comment into the database
     *
     * @param string $comment
     * @param int    $userId
     */
    protected function updateInternalUserComment($comment, $userId)
    {
        $sql = "UPDATE `s_user` SET internalcomment = concat(internalcomment, " . Shopware()->Db()->quote($comment)
            . ") WHERE id=" . $userId;
        Shopware()->Db()->query($sql);
    }

    /**
     * @param string           $email
     * @param string           $pass
     * @param ShopgateCustomer $customer
     * @param ShopgateAddress  $billingAddress
     * @param ShopgateAddress  $shippingAddress
     * @param bool             $createGuest
     *
     * @return array
     */
    protected function createRegistrationPostData(
        $email,
        $pass,
        $customer,
        $billingAddress,
        $shippingAddress,
        $createGuest = false
    ) {
        $billing  = $this->createAddress($customer, $billingAddress);
        $shipping = $this->createAddress($customer, $shippingAddress);

        // datafields to be filled out and used later
        $postData = array(
            'personal' => array(
                'customer_type'        => 'private',
                'salutation'           => $billing['salutation'],
                'firstname'            => $billing['firstname'],
                'lastname'             => $billing['lastname'],
                'skipLogin'            => '0',
                'email'                => $email,
                'password'             => $pass,
                'passwordConfirmation' => $pass,
                'phone'                => $billing['phone'],
                'birthday'             => $billing['birthday'],
                'birthmonth'           => $billing['birthmonth'],
                'birthyear'            => $billing['birthyear'],
            ),
            'billing'  => array(
                'company'      => $billing['company'],
                'department'   => $billing['department'],
                'ustid'        => $billing['ustid'],
                'street'       => $billing['street'],
                'streetnumber' => $billing['streetnumber'],
                'zipcode'      => $billing['zipcode'],
                'city'         => $billing['city'],
                'country'      => $billing['country'],
                'stateID'      => $billing['stateID'],
            ),
            'shipping' => array(),
        );
        if ($shippingAddress) {
            $postData['shipping'] = array(
                'salutation'   => $shipping['salutation'],
                'company'      => $shipping['company'],
                'department'   => $shipping['department'],
                'firstname'    => $shipping['firstname'],
                'lastname'     => $shipping['lastname'],
                'street'       => $shipping['street'],
                'streetnumber' => $shipping['streetnumber'],
                'zipcode'      => $shipping['zipcode'],
                'city'         => $shipping['city'],
                'country'      => $shipping['country'],
                'stateID'      => $shipping['stateID'],
            );
        }

        if (!$createGuest) {
            unset($postData['personal']['skipLogin']);
        } else {
            $postData['personal']['skipLogin']            = "1";
            $postData['personal']['password']             = '';
            $postData['personal']['passwordConfirmation'] = '';
            $postData['personal']['birthday']             = '';
            $postData['personal']['birthmonth']           = '';
            $postData['personal']['birthyear']            = '';
        }

        return $postData;
    }

    /**
     * @param ShopgateCustomer $customer
     * @param ShopgateAddress  $address
     *
     * @return array
     */
    protected function createAddress($customer, $address)
    {
        $addressArr = array();

        if ($address) {
            $salutation = self::NOT_DEFINED;
            if (!empty(self::GENDER_SALUTATION_MAP[$address->getGender()])) {
                $salutation = self::GENDER_SALUTATION_MAP[$address->getGender()];
            }

            $country = $this->getCountryByIso($address->getCountry());
            $state   = $this->getStateByIso($address->getState());

            $addressArr['salutation'] = $salutation;
            $addressArr['firstname']  = $address->getFirstName();
            $addressArr['lastname']   = $address->getLastName();
            $addressArr['company']    = $address->getCompany()
                ? $address->getCompany()
                : '';
            $addressArr['department'] = '';

            if (!$this->config->assertMinimumVersion('5.0.0')) {
                $addressArr['street'] = $address->getStreetName1();
                // order number: 1502360169 irish address without a street number caused problems. We need to fake the street number here
                $addressArr['streetnumber'] = $address->getStreetNumber1()
                    ? $address->getStreetNumber1()
                    : '.';
            } else {
                $addressArr['street'] = $address->getStreet1();
            }

            $addressArr['city']     = $address->getCity();
            $addressArr['zipcode']  = $address->getZipcode();
            $addressArr['country']  = $country->getId();
            $addressArr['stateID']  = $state
                ? $state->getId()
                : 0;
            $addressArr['phone']    = '';
            $addressArr['fax']      = '';
            $addressArr['ustid']    = '';
            $addressArr['birthday'] = '';

            foreach ($address->getCustomFields() as $customField) {
                if (isset($addressArr[$customField->getInternalFieldName()])
                    && empty($addressArr[$customField->getInternalFieldName()])
                ) {
                    $addressArr[$customField->getInternalFieldName()] = $customField->getValue();
                }
            }

            // set phone number
            if (empty($addressArr['phone'])) {
                $addressArr['phone'] = $this->getPhoneNumber($customer, $address);
            }

            $birthdate = empty($addressArr['birthday'])
                ? $this->getBirthday($customer, $address)
                : $addressArr['birthday'];

            $birthdate                = explode('-', $birthdate);
            $addressArr['birthday']   = isset($birthdate[2]) ? $birthdate[2] : '';
            $addressArr['birthmonth'] = isset($birthdate[1]) ? $birthdate[1] : '';
            $addressArr['birthyear']  = isset($birthdate[0]) ? $birthdate[0] : '';
        }

        return $addressArr;
    }

    /**
     * @param string           $email
     * @param string           $pass
     * @param ShopgateCustomer $customer
     * @param bool             $createGuest
     *
     * @return int|null
     *
     * @throws ShopgateLibraryException
     */
    protected function createNewCustomerNew($email, $pass, $customer, $createGuest = false)
    {
        $salutation = self::NOT_DEFINED;
        if (!empty(self::GENDER_SALUTATION_MAP[$customer->getGender()])) {
            $salutation = self::GENDER_SALUTATION_MAP[$customer->getGender()];
        }

        $accountMode = $createGuest
            ? 1
            : 0;

        $customerData = array(
            'shopId'      => Shopware()->Shop()->getId(),
            'paymentID'   => 5,
            'accountMode' => $accountMode,
            'email'       => $email,
            'password'    => $pass,
            'firstName'   => $customer->getFirstName(),
            'lastName'    => $customer->getLastName(),
            'salutation'  => $salutation,
            'birthday'    => $this->getBirthday($customer),
        );

        /** @var ShopgateAddress $shopgateCustomerAddress */
        foreach ($customer->getAddresses() as $shopgateCustomerAddress) {
            $prefix = $shopgateCustomerAddress->getIsInvoiceAddress()
                ? 'billing'
                : 'shipping';

            $salutation = self::NOT_DEFINED;
            if (!empty(self::GENDER_SALUTATION_MAP[$shopgateCustomerAddress->getGender()])) {
                $salutation = self::GENDER_SALUTATION_MAP[$shopgateCustomerAddress->getGender()];
            }

            $country = $this->getCountryByIso($shopgateCustomerAddress->getCountry());
            $state   = $this->getStateByIso($shopgateCustomerAddress->getState());

            $customerData[$prefix]['salutation'] = $salutation;
            $customerData[$prefix]['firstName']  = $shopgateCustomerAddress->getFirstName();
            $customerData[$prefix]['lastName']   = $shopgateCustomerAddress->getLastName();
            $customerData[$prefix]['company']    =
                $shopgateCustomerAddress->getCompany()
                    ? $shopgateCustomerAddress->getCompany()
                    : '';
            $customerData[$prefix]['street']     = $shopgateCustomerAddress->getStreet1();
            $customerData[$prefix]['city']       = $shopgateCustomerAddress->getCity();
            $customerData[$prefix]['zipcode']    = $shopgateCustomerAddress->getZipcode();
            $customerData[$prefix]['country']    = $country ? $country->getId() : 0;
            $customerData[$prefix]['stateID']    = $state
                ? $state->getId()
                : 0;
            $customerData[$prefix]['state']      = $customerData[$prefix]['stateID'];
            $customerData[$prefix]['phone']      = $this->getPhoneNumber($customer, $shopgateCustomerAddress);
        }

        try {
            /** @var \Shopware\Components\Api\Resource\Customer $customerResource */
            $customerResource = \Shopware\Components\Api\Manager::getResource('customer');

            // suspend auto-commit
            Shopware()->Models()->getConnection()->beginTransaction();
            $customer   = $customerResource->create($customerData);
            $customerId = $customer->getId();

            // commit
            Shopware()->Models()->getConnection()->commit();
        } catch (\Shopware\Components\Api\Exception\ValidationException $e) {

            // rollback in case of exceptions
            Shopware()->Models()->getConnection()->rollback();
            $errors = array();
            /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
            foreach ($e->getViolations() as $violation) {
                $errors[] = sprintf(
                    '%s: %s',
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                );
            }
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER, print_r($errors, true),
                true
            );
        } catch (Exception $e) {
            // rollback in case of exceptions
            Shopware()->Models()->getConnection()->rollback();
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $e->getMessage(), true);
        }

        return $customerId;
    }

    /**
     *
     * @param string $iso
     *
     * @return \Shopware\Models\Country\State | null
     */
    public function getStateByIso($iso)
    {
        $matches = array();
        if (!preg_match("/^(?P<country>..+)\-(?P<state>..+)$/i", $iso, $matches)) {
            return null;
        }

        $dql = "
			SELECT s
			FROM \Shopware\Models\Country\State s
			JOIN s.country c
			WHERE (s.shortCode = :stateISO OR s.shortCode = :fullISO)
			  AND c.iso = :countryISO";

        /* @var $state \Shopware\Models\Country\State | null */
        $state = Shopware()->Models()->createQuery($dql)
            ->setMaxResults(1)
            ->setParameter("stateISO", $matches["state"])
            ->setParameter("fullISO", $iso)
            ->setParameter("countryISO", $matches["country"])
            ->getOneOrNullResult();

        return $state;
    }

    /**
     *
     * @param string $iso
     *
     * @return \Shopware\Models\Country\Country
     */
    public function getCountryByIso($iso)
    {
        $dql = "SELECT c FROM \Shopware\Models\Country\Country c WHERE c.iso = :iso";

        return Shopware()->Models()->createQuery($dql)
            ->setMaxResults(1)
            ->setParameter("iso", $iso)
            ->getOneOrNullResult();
    }

    /**
     * @param ShopgateCustomer $customer
     * @param ShopgateAddress  $customerAddress
     *
     * @return string
     */
    protected function getPhoneNumber($customer, $customerAddress)
    {
        $phoneNumber = self::DEFAULT_PHONE_NUMBER;

        if ($customerAddress->getPhone()) {
            $phoneNumber = $customerAddress->getPhone();
        } elseif ($customerAddress->getMobile()) {
            $phoneNumber = $customerAddress->getMobile();
        } elseif ($customer->getPhone()) {
            $phoneNumber = $customer->getPhone();
        } elseif ($customer->getMobile()) {
            $phoneNumber = $customer->getMobile();
        }

        return $phoneNumber;
    }

    /**
     * @param ShopgateCustomer     $customer
     * @param ShopgateAddress|null $customerAddress
     *
     * @return string
     */
    protected function getBirthday($customer, $customerAddress = null)
    {
        $birthdate = self::DEFAULT_BIRTHDATE;
        if (!is_null($customerAddress) && $customerAddress->getBirthday()) {
            $birthdate = $customerAddress->getBirthday();
        } elseif ($customer->getBirthday()) {
            $birthdate = $customer->getBirthday();
        }

        return $birthdate;
    }
}
