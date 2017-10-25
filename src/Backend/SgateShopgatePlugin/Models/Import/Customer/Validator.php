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
 * Class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Import_Customer_Validator
 */
class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Import_Customer_Validator
{
    /** @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config */
    protected $config;

    public function __construct($config = null)
    {
        if ($config instanceof Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config) {
            $this->config = $config;
        } else {
            $this->config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        }
    }

    /**
     * Function that is nearly identical to a Shopware frontend controller method (with minor changes)
     *
     * @param array &$postData
     *
     * @throws Enlight_Event_Exception
     * @throws ShopgateLibraryException
     */
    public function registrationValidatePersonal(&$postData)
    {
        Shopware()->Modules()->Admin()->sSYSTEM->_POST = $postData['personal'];

        $checkData = Shopware()->Modules()->Admin()->sValidateStep1();
        if (!empty($checkData['sErrorMessages'])) {
            $result = $checkData;
        }

        $rules = array(
            'customer_type' => array('required' => 0),
            'salutation'    => array('required' => 1),
            'firstname'     => array('required' => 1),
            'lastname'      => array('required' => 1),
            'phone'         => array('required' => 1),
            'fax'           => array('required' => 0),
            'text1'         => array('required' => 0),
            'text2'         => array('required' => 0),
            'text3'         => array('required' => 0),
            'text4'         => array('required' => 0),
            'text5'         => array('required' => 0),
            'text6'         => array('required' => 0),
            'sValidation'   => array('required' => 0),
            'birthyear'     => array('required' => 0),
            'birthmonth'    => array('required' => 0),
            'birthday'      => array('required' => 0),
            'dpacheckbox'   => array('required' => 0),
        );
        # validation by third-party plugins is more likely to cause problems than do any good, so we deactivate it
        # (see SHOPWARE-312)
        # $rules = Enlight()->Events()->filter('Shopware_Controllers_Frontend_Register_validatePersonal_FilterRules', $rules, array('subject'=>$this));
        $checkData = Shopware()->Modules()->Admin()->sValidateStep2($rules);

        if (!empty($checkData['sErrorMessages'])) {
            $result = array_merge_recursive($result, $checkData);
        }

        if (!empty($result) && !empty($result['sErrorMessages'])) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER, print_r($result, true),
                true
            );
        }
    }

    /**
     * Function that is nearly identical to a Shopware frontend controller method (with minor changes)
     *
     * @param array &$postData
     *
     * @throws Enlight_Event_Exception
     * @throws ShopgateLibraryException
     */
    public function registrationValidateBilling(&$postData)
    {
        $rules = array(
            'company'         => array('required' => 0),
            'street'          => array('required' => 1),
            'streetnumber'    => array('required' => !$this->config->assertMinimumVersion('5.0.0')),
            'zipcode'         => array('required' => 1),
            'city'            => array('required' => 1),
            'country'         => array('required' => 1),
            'department'      => array('required' => 0),
            'shippingAddress' => array('required' => 0),
        );

        // Check if state selection is required
        if (!empty($postData["billing"]["country"])) {
            $stateSelectionRequired = Shopware()->Db()->fetchRow(
                "
			  SELECT display_state_in_registration, force_state_in_registration
			  FROM s_core_countries WHERE id = ?
			  ",
                array($postData["billing"]["country"])
            );
            if ($stateSelectionRequired["display_state_in_registration"] == true
                && $stateSelectionRequired["force_state_in_registration"] == true
            ) {
                $rules["stateID"] = array("required" => true);
            } else {
                $rules["stateID"] = array("required" => false);
            }
        }

        if (!empty($postData['personal']['sValidation'])) {
            $postData['personal']['customer_type'] = 'business';
        }

        if (!empty($postData['personal']['customer_type'])
            && $postData['personal']['customer_type'] == 'business'
        ) {
            $rules['company'] = array('required' => 1);
            $rules['ustid']   = array(
                'required' => (Shopware()->Config()->vatCheckRequired && Shopware()->Config()->vatCheckEndabled),
            );
        }
        $rules = Enlight()->Events()->filter(
            'Shopware_Controllers_Frontend_Register_validateBilling_FilterRules',
            $rules,
            array('subject' => $this)
        );

        Shopware()->Modules()->Admin()->sSYSTEM->_POST = $postData['billing'];

        $checkData = Shopware()->Modules()->Admin()->sValidateStep2($rules, false);

        if (!empty($checkData) && !empty($checkData['sErrorMessages'])) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER, print_r($checkData, true),
                true
            );
        }
    }

    /**
     * Function that is nearly identical to a Shopware frontend controller method (with minor changes)
     *
     * @param array &$postData
     *
     * @throws Enlight_Event_Exception
     * @throws ShopgateLibraryException
     */
    public function registrationValidateShipping(&$postData)
    {
        if (!empty($postData['shipping'])) {
            $rules = array(
                'salutation'   => array('required' => 1),
                'company'      => array('required' => 0),
                'firstname'    => array('required' => 1),
                'lastname'     => array('required' => 1),
                'street'       => array('required' => 1),
                'streetnumber' => array('required' => !$this->config->assertMinimumVersion('5.0.0')),
                'zipcode'      => array('required' => 1),
                'city'         => array('required' => 1),
                'department'   => array('required' => 0),
                'text1'        => array('required' => 0),
                'text2'        => array('required' => 0),
                'text3'        => array('required' => 0),
                'text4'        => array('required' => 0),
                'text5'        => array('required' => 0),
                'text6'        => array('required' => 0),
                'country'      => array(
                    'required' => (Shopware()->Config()->get('sCOUNTRYSHIPPING'))
                        ? 1
                        : 0,
                ),
            );

            // Check if state selection is required
            if (!empty($postData["shipping"]["country"])
                && Shopware()->Config()->get('sCOUNTRYSHIPPING') == true
            ) {
                $stateSelectionRequired = Shopware()->Db()->fetchRow(
                    "
			        SELECT display_state_in_registration, force_state_in_registration
			        FROM s_core_countries WHERE id = ?",
                    array($postData["shipping"]["country"])
                );
                if ($stateSelectionRequired["display_state_in_registration"] == true
                    && $stateSelectionRequired["force_state_in_registration"] == true
                ) {
                    $rules["stateID"] = array("required" => true);
                } else {
                    $rules["stateID"] = array("required" => false);
                }
            }

            $rules = Enlight()->Events()->filter(
                'Shopware_Controllers_Frontend_Register_validateShipping_FilterRules',
                $rules,
                array('subject' => $this)
            );

            Shopware()->Modules()->Admin()->sSYSTEM->_POST = $postData['shipping'];

            $checkData = Shopware()->Modules()->Admin()->sValidateStep2ShippingAddress($rules);

            if (!empty($checkData) && !empty($checkData['sErrorMessages'])) {
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER, print_r($checkData, true),
                    true
                );
            }
        }
    }
}
