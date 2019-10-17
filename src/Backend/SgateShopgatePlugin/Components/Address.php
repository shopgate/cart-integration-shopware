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

class Address
{
    /**
     * @var WebCheckout
     */
    protected $webCheckoutHelper;

    /**
     * Reference to Shopware model object (Shopware()->Models)
     *
     * @var Shopware\Components\Model\ModelManager
     */
    protected $models;

    /**
     * Reference to Shopware container object (Shopware()->Container)
     *
     * @var Container
     */
    protected $container;

    /**
     * Address constructor.
     */
    public function __construct()
    {
        $this->webCheckoutHelper = new WebCheckout();
        $this->models            = Shopware()->Models();
        $this->container         = Shopware()->Container();
    }

    /**
     * Custom action to get the address data of an user
     *
     * @param Enlight_Controller_Request_Request $request
     *
     * @return array
     */
    public function getAddressesAction($request)
    {
        $data = $this->webCheckoutHelper->getJWT($request->getCookie('token'));

        if (isset($data['error']) && $data['error']) {
            return $data;
        }

        $customer               = $this->webCheckoutHelper->getCustomer($data['customer_id']);
        $defaultBillingAddress  = $customer->getDefaultBillingAddress();
        $defaultShippingAddress = $customer->getDefaultShippingAddress();

        $addressRepository = $this->models->getRepository("Shopware\\Models\\Customer\\Address");

        $addresses = $addressRepository->getListArray($customer->getId());

        // Create a list of ids of occurring countries and states
        $countryIds = array_unique(array_filter(array_column($addresses, 'countryId')));
        $stateIds   = array_unique(array_filter(array_column($addresses, 'stateId')));

        $countryRepository = $this->container->get('shopware_storefront.country_gateway');
        $context           = $this->container->get('shopware_storefront.context_service')->getShopContext();

        $countries = $countryRepository->getCountries($countryIds, $context);
        $states    = $countryRepository->getStates($stateIds, $context);

        // Apply translations for countries and states to address array, converting them from structs to arrays in the process
        foreach ($addresses as &$address) {
            if (array_key_exists($address['countryId'], $countries)) {
                $address['country'] = json_decode(json_encode($countries[$address['countryId']]), true);
            }
            if (array_key_exists($address['stateId'], $states)) {
                $address['state'] = json_decode(json_encode($states[$address['stateId']]), true);
            }

            $customerAddress                   = $addressRepository->getOneByUser($address['id'], $customer->getId());
            $address['additional']             = $customerAddress->getAdditional();
            $address['defaultBillingAddress']  = $defaultBillingAddress->getId() === $address['id'];
            $address['defaultShippingAddress'] = $defaultShippingAddress->getId() === $address['id'];
        }
        unset($address);

        return $addresses;
    }

    /**
     * Custom action to add a new address
     *
     * @param Enlight_Controller_Request_Request $request
     *
     * @return array
     */
    public function addAddressAction($request)
    {
        $data = $this->getAddressData($request);

        if (isset($data['error']) && $data['error']) {
            return $data;
        }

        $customer = $this->webCheckoutHelper->getCustomer($data['customer_id']);

        if ($request->isPut()) {
            $addressRepository = $this->models->getRepository("Shopware\\Models\\Customer\\Address");
            $address           = $addressRepository->getOneByUser((int)$data['address']['id'], $customer->getId());
        } else {
            $address = new \Shopware\Models\Customer\Address();
        }

        $form = $this->createForm('Shopware\\Bundle\\AccountBundle\\Form\\Account\\AddressFormType', $address);
        $form->submit($data['address']);

        return $this->saveFormData($form, $address, $customer);
    }

    /**
     * Custom action to delete an address
     *
     * @param Enlight_Controller_Request_Request $request
     *
     * @return array
     */
    public function deleteAddressAction($request)
    {
        $params = $this->webCheckoutHelper->getJsonParams($request);
        $data   = $this->webCheckoutHelper->getJWT($params['token']);

        if (isset($data['error']) && $data['error']) {
            return $data;
        }

        $customer          = $this->webCheckoutHelper->getCustomer($data['customer_id']);
        $addressService    = $this->container->get('shopware_account.address_service');
        $addressRepository = $this->models->getRepository("Shopware\\Models\\Customer\\Address");

        foreach ($data['addressIds'] as $addressId) {
            $address = $addressRepository->getOneByUser((int)$addressId, $customer->getId());
            $addressService->delete($address);
        }

        return array('success' => true);
    }

    /**
     * @param Enlight_Controller_Request_Request $request
     *
     * @return array
     */
    private function getAddressData($request)
    {
        $params = $this->webCheckoutHelper->getJsonParams($request);
        $data   = $this->webCheckoutHelper->getJWT($params['token']);

        if (isset($data['error']) && $data['error']) {
            return $data;
        }

        if (!empty($data['address']['country'])) {
            $query = $this->models->getConnection()->createQueryBuilder();
            $query->select('id')
                  ->from('s_core_countries', 'country')
                  ->where('country.countryiso = :iso')
                  ->setParameter('iso', $data['address']['country']);

            $countryId                  = $query->execute()->fetch();
            $data['address']['country'] = $countryId['id'];
        }

        if (!empty($data['address']['state'])) {
            $query = $this->models->getConnection()->createQueryBuilder();
            $query->select('id')
                  ->from('s_core_countries_states', 'state')
                  ->where('state.countryID = :id')
                  ->andwhere('state.shortcode = :code')
                  ->setParameter('id', $countryId['id'])
                  ->setParameter('code', $data['address']['state']);

            $stateId                  = $query->execute()->fetch();
            $data['address']['state'] = $stateId['id'];
        }

        return $data;
    }

    /**
     * @param $form
     * @param $address
     * @param $customer
     *
     * @return string
     */
    private function saveFormData($form, $address, $customer)
    {
        if ($form->isValid()) {
            $addressService = $this->container->get('shopware_account.address_service');
            $addressService->create($address, $customer);

            $additional = $address->getAdditional();

            if (!empty($additional['setDefaultBillingAddress'])) {
                $addressService->setDefaultBillingAddress($address);
            }

            if (!empty($additional['setDefaultShippingAddress'])) {
                $addressService->setDefaultShippingAddress($address);
            }

            return array('success' => true);
        } else {
            $errors = $form->getErrors(true);
            $string = '';
            foreach ($errors as $error) {
                $string .= $error->getOrigin()->getName() . $error->getMessage() . "\n";
            }

            return array(
                'success' => false,
                'message' => $string
            );
        }
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string $type    The fully qualified class name of the form type
     * @param mixed  $data    The initial data for the form
     * @param array  $options Options for the form
     *
     * @throws \Exception
     *
     * @return Form
     */
    protected function createForm($type, $data = null, array $options = array())
    {
        return $this->container->get('shopware.form.factory')->create($type, $data, $options);
    }
}
