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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Settings
{
    /**
     * @var int
     */
    protected $defaultCustomerGroup;

    /**
     * @var array
     */
    private $allowedCountries = null;

    /**
     * @param int $defaultCustomerGroup
     */
    public function __construct($defaultCustomerGroup)
    {
        $this->defaultCustomerGroup = $defaultCustomerGroup;
    }

    /**
     * @return array
     */
    public function getTaxSettings()
    {
        $productTaxClasses  = array();
        $CustomerTaxClasses = $this->getCustomerTaxClasses();
        $taxRates           = array();
        $taxRules           = array();
        $taxRateKeysForAll  = array();

        $taxFreeCountries = $this->getTaxFreeCountries();
        foreach ($taxFreeCountries as $taxFreeCountry) {
            $key                 = strtolower("rate_{$taxFreeCountry}_free");
            $taxRates[]          = array(
                "key"                => $key,
                "display_name"       => '0%',
                "tax_percent"        => 0,
                "country"            => $taxFreeCountry,
                "state"              => '',
                "zipcode_type"       => 'all',
                "zipcode_pattern"    => '',
                "zipcode_range_from" => '',
                "zipcode_range_to"   => '',
            );
            $taxRateKeysForAll[] = array("key" => $key);
        }

        $qry = Shopware()->Models()->createQuery("SELECT c FROM \Shopware\Models\Tax\Tax c");

        foreach ($qry->getResult() as $tax) {
            /** @var $tax \Shopware\Models\Tax\Tax */
            $taxRateKeys = array();

            $productTaxClassKey  = "tax_{$tax->getTax()}_{$tax->getId()}";
            $productTaxClass     = array(
                'key' => $productTaxClassKey,
                'id'  => $tax->getId(),
            );
            $productTaxClasses[] = $productTaxClass;

            $key               = strtolower("rate_{$tax->getTax()}_{$tax->getId()}_default");
            $taxRates[]        = array(
                "id"                 => $tax->getId(),
                "key"                => $key,
                "display_name"       => $tax->getName(),
                "tax_percent"        => $tax->getTax(),
                "country"            => "",
                "state"              => "",
                "zipcode_type"       => "all",
                "zipcode_pattern"    => "",
                "zipcode_range_from" => "",
                "zipcode_range_to"   => "",
            );
            $defaultTaxRateKey = array("key" => $key);

            foreach ($tax->getRules() as $rule) {
                /* @var $rule \Shopware\Models\Tax\Rule */
                if ($rule->getActive() != 1) {
                    continue;
                }
                try {
                    $customerGroupKey = $rule->getCustomerGroup()->getKey();
                } catch (Exception $e) {
                    continue;
                }

                if ($rule->getArea() && !$rule->getCountry()) {
                    foreach ($rule->getArea()->getCountries() as $country) {
                        /* @var $country \Shopware\Models\Country\Country */
                        $iso = $country->getIso();
                        if (in_array($iso, $taxFreeCountries)) {
                            continue;
                        }
                        $state = $rule->getState()
                            ? $rule->getState()->getShortCode()
                            : "";

                        $key                              = strtolower(
                            "rate_{$tax->getTax()}_{$tax->getId()}_{$customerGroupKey}_{$iso}_{$rule->getId()}"
                        );
                        $taxRates[]                       = array(
                            "id"                 => $country->getId() . $rule->getId(), // TODO
                            "key"                => $key,
                            "display_name"       => $rule->getName()
                                ?: "{$iso} {$rule->getTax()}%",
                            "tax_percent"        => $rule->getTax(),
                            "country"            => $iso,
                            "state"              => $state,
                            "zipcode_type"       => "all",
                            "zipcode_pattern"    => "",
                            "zipcode_range_from" => "",
                            "zipcode_range_to"   => "",
                        );
                        $taxRateKeys[$customerGroupKey][] = array("key" => $key);
                    }
                } else {
                    $iso   = $rule->getCountry()
                        ? $rule->getCountry()->getIso()
                        : "";
                    $state = ($rule->getState() && $rule->getState()->getId())
                        ? $rule->getState()->getShortCode()
                        : "";
                    if (!$rule->getCountry() && $rule->getState()) {
                        $iso = $rule->getState()->getCountry()->getIso();
                    }
                    if (in_array($iso, $taxFreeCountries)) {
                        continue;
                    }

                    $key                              = strtolower(
                        "rate_{$tax->getTax()}_{$tax->getId()}_{$customerGroupKey}_{$iso}_{$rule->getId()}"
                    );
                    $taxRates[]                       = array(
                        "key"                => $key,
                        "display_name"       => $rule->getName()
                            ?: "{$rule->getTax()}%",
                        "tax_percent"        => $rule->getTax(),
                        "country"            => $iso,
                        "state"              => $state,
                        "zipcode_type"       => "all",
                        "zipcode_pattern"    => "",
                        "zipcode_range_from" => "",
                        "zipcode_range_to"   => "",
                    );
                    $taxRateKeys[$customerGroupKey][] = array("key" => $key);
                }
            }

            foreach ($CustomerTaxClasses as $CustomerTaxClass) {
                $CustomerTaxClassId  = $CustomerTaxClass['id'];
                $CustomerTaxClassKey = $CustomerTaxClass['key'];
                $taxRule             = array(
                    "id"                   => $tax->getId() . '_' . $CustomerTaxClassId,
                    "key"                  => strtolower(
                        "rule_" . $tax->getId() . '_' . $tax->getTax() . '_' . $CustomerTaxClassKey
                    ),
                    "name"                 => $tax->getName() . '_' . $CustomerTaxClassKey,
                    "priority"             => 0,
                    "product_tax_classes"  => array(
                        array(
                            "key" => $productTaxClassKey,
                        ),
                    ),
                    "customer_tax_classes" => array(
                        array(
                            "id"  => $CustomerTaxClassId,
                            "key" => $CustomerTaxClassKey,
                        ),
                    ),
                    "tax_rates"            => array(),
                );

                foreach ($taxRateKeysForAll as $taxRateKeyForAll) {
                    $taxRule["tax_rates"][] = $taxRateKeyForAll;
                }
                foreach ($taxRateKeys[$CustomerTaxClassKey] as $taxRateKey) {
                    $taxRule["tax_rates"][] = $taxRateKey;
                }
                $taxRule["tax_rates"][] = $defaultTaxRateKey;
                $taxRules[]             = $taxRule;
            }
        }

        $settings                         = array();
        $settings['product_tax_classes']  = $productTaxClasses;
        $settings['customer_tax_classes'] = $CustomerTaxClasses;
        $settings['tax_rates']            = $taxRates;
        $settings['tax_rules']            = $taxRules;

        return $settings;
    }

    /**
     * @param bool $short
     *
     * @return array
     */
    public function getProductTaxClasses($short = false)
    {
        $classes = array();
        $qry     = Shopware()->Models()->createQuery("SELECT c FROM \Shopware\Models\Tax\Tax c");

        foreach ($qry->getResult() as $tax) {
            /** @var $tax \Shopware\Models\Tax\Tax */

            $class = array(
                'key' => "tax_{$tax->getTax()}",
            );
            if (!$short) {
                $class['id'] = $tax->getId();
            }
            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * @param bool $short
     *
     * @return array
     */
    public function getCustomerTaxClasses($short = false)
    {
        $classes = array();

        $qry = Shopware()->Models()->createQuery(
            "SELECT g FROM \Shopware\Models\Customer\Group g"
        );

        foreach ($qry->getResult() as $group) {
            /** @var $group \Shopware\Models\Customer\Group */

            $class = array(
                'key' => $group->getKey(),
            );
            if (!$short) {
                $class['id']         = $group->getId();
                $class['is_default'] = (($group->getKey() == $this->defaultCustomerGroup)
                    ? "1"
                    : "0");
            }
            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * @return array
     */
    private function getTaxFreeCountries()
    {
        $countries = Shopware()->Models()->createQuery(
            "SELECT c.iso FROM '\Shopware\Models\Country\Country' c WHERE c.taxFree = 1"
        )->getResult();
        $result    = array();
        foreach ($countries as $country) {
            $result[] = $country['iso'];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getTaxRates()
    {
        $rates = array();
        $qry   = Shopware()->Models()->createQuery("SELECT c FROM \Shopware\Models\Tax\Tax c");

        $taxFreeCountries = $this->getTaxFreeCountries();

        foreach ($qry->getResult() as $tax) {
            /* @var $tax \Shopware\Models\Tax\Tax */

            $rates[] = array(
                "id"                 => $tax->getId(),
                "key"                => "rate_default_{$tax->getTax()}",
                "display_name"       => $tax->getName(),
                "tax_percent"        => $tax->getTax(),
                "country"            => "",
                "state"              => "",
                "zipcode_type"       => "all",
                "zipcode_pattern"    => "",
                "zipcode_range_from" => "",
                "zipcode_range_to"   => "",
            );

            foreach ($tax->getRules() as $rule) {
                /* @var $rule \Shopware\Models\Tax\Rule */

                if ($rule->getArea() && !$rule->getCountry()) {
                    foreach ($rule->getArea()->getCountries() as $country) {
                        /* @var $country \Shopware\Models\Country\Country */
                        $iso = $country->getIso();
                        if (in_array($iso, $taxFreeCountries)) {
                            continue;
                        }
                        $state = $rule->getState()
                            ? $rule->getState()->getShortCode()
                            : "";

                        $rates[] = array(
                            "id"                 => $country->getId() . $rule->getId(), // TODO
                            "key"                => strtolower("rate_{$iso}_{$rule->getTax()}"),
                            "display_name"       => $rule->getName()
                                ?: "{$iso} {$rule->getTax()}%",
                            "tax_percent"        => $rule->getTax(),
                            "country"            => $iso,
                            "state"              => $state,
                            "zipcode_type"       => "all",
                            "zipcode_pattern"    => "",
                            "zipcode_range_from" => "",
                            "zipcode_range_to"   => "",
                        );
                    }
                } else {
                    $country = $rule->getCountry()
                        ? $rule->getCountry()->getIso()
                        : "";
                    $state   = $rule->getState()
                        ? $rule->getState()->getShortCode()
                        : "";
                    if (!$rule->getCountry() && $rule->getState()) {
                        $country = $rule->getState()->getCountry()->getIso();
                    }
                    if (in_array($country, $taxFreeCountries)) {
                        continue;
                    }

                    $rates[] = array(
                        "key"                => strtolower("rate_{$country}_{$rule->getTax()}"),
                        "display_name"       => $rule->getName()
                            ?: "{$rule->getTax()}%",
                        "tax_percent"        => $rule->getTax(),
                        "country"            => $country,
                        "state"              => $state,
                        "zipcode_type"       => "all",
                        "zipcode_pattern"    => "",
                        "zipcode_range_from" => "",
                        "zipcode_range_to"   => "",
                    );
                }
            }
        }

        foreach ($taxFreeCountries as $country) {
            $rates[] = array(
                "key"                => strtolower("rate_{$country}_free"),
                "display_name"       => '0%',
                "tax_percent"        => 0,
                "country"            => $country,
                "state"              => '',
                "zipcode_type"       => 'all',
                "zipcode_pattern"    => '',
                "zipcode_range_from" => '',
                "zipcode_range_to"   => '',
            );
        }

        return $rates;
    }

    /**
     * @return array
     */
    public function getTaxRules()
    {
        $result           = array();
        $taxFreeCountries = $this->getTaxFreeCountries();
        $taxQry           = Shopware()->Models()->createQuery("SELECT c FROM \Shopware\Models\Tax\Tax c");
        foreach ($taxQry->getResult() as $tax) {
            /* @var $tax \Shopware\Models\Tax\Tax */

            $groupQry =
                Shopware()->Models()->createQuery("SELECT g FROM \Shopware\Models\Customer\Group g WHERE g.tax = 1");

            foreach ($groupQry->getResult() as $group) {
                /* @var $group \Shopware\Models\Customer\Group */
                $_rule = array(
                    // 					"id" => $group->getId().$tax->getId(),
                    "key"                  => strtolower("rule_{$group->getId()}_{$tax->getId()}_default"),
                    "name"                 => $tax->getName() . '_default',
                    "priority"             => 0,
                    "product_tax_classes"  => array(
                        array(
                            // 							"id" => $tax->getId(),
                            "key" => "tax_{$tax->getTax()}",
                        ),
                    ),
                    "customer_tax_classes" => array(),
                );

                $_rule["tax_rates"][] = array(
                    // 					"id" => $tax->getId(),
                    "key" => "rate_default_{$tax->getTax()}",
                );

                foreach ($taxFreeCountries as $country) {
                    $_rule["tax_rates"][] = array(
                        'key' => strtolower("rate_{$country}_free"),
                    );
                }

                $_rule["customer_tax_classes"][] = array(
                    // 					"id" => $group->getId(),
                    "key" => $group->getKey(),
                );

                $result[] = $_rule;

                $_rule['key']       = substr($_rule['key'], 0, -8);
                $_rule['name']      = substr($_rule['name'], 0, -8);
                $_rule['tax_rates'] = array();
                $_rule['priority']  = 1;

                $rules = Shopware()->Models()->createQuery(
                    "SELECT r FROM \Shopware\Models\Tax\Rule r
                         WHERE r.groupId = :taxGroup AND r.customerGroupId = :customerGroup"
                )->setParameter("taxGroup", $tax->getId())
                    ->setParameter(
                        "customerGroup",
                        $group->getId()
                    )->getResult();

                if (empty($rules)) {
                    continue;
                }

                foreach ($rules as $rule) {
                    /* @var $rule \Shopware\Models\Tax\Rule */
                    if ($rule->getArea() && !$rule->getCountry()) {
                        foreach ($rule->getArea()->getCountries() as $country) {
                            /* @var $country \Shopware\Models\Country\Country */
                            $iso = $country->getIso();
                            if (in_array($iso, $taxFreeCountries)) {
                                continue;
                            }
                            $state = $rule->getState()
                                ? $rule->getState()->getShortCode()
                                : "";

                            $_rule["tax_rates"][] = array(
                                // 								"id" =>  $country->getId().$rule->getId(), // TODO
                                "key" => strtolower("rate_{$iso}_{$rule->getTax()}"),
                            );
                        }
                    } else {
                        $country = $rule->getCountry()
                            ? $rule->getCountry()->getIso()
                            : "";
                        $state   = $rule->getState()
                            ? $rule->getState()->getShortCode()
                            : "";
                        if (!$rule->getCountry() && $rule->getState()) {
                            $country = $rule->getState()->getCountry()->getIso();
                        }
                        if (in_array($country, $taxFreeCountries)) {
                            continue;
                        }

                        $_rule["tax_rates"][] = array(
                            // 							"id" => $rule->getId(),
                            "key" => strtolower("rate_{$country}_{$rule->getTax()}"),
                        );
                    }
                }
                $result[] = $_rule;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getCustomerGroups()
    {
        $groups = array();
        $qry    = Shopware()->Models()->createQuery(
            "SELECT g FROM \Shopware\Models\Customer\Group g"
        );

        foreach ($qry->getResult() as $group) {
            /* @var $group \Shopware\Models\Customer\Group */
            $groups[] = array(
                'id'                     => $group->getId(),
                'name'                   => $group->getName(),
                'is_default'             => (($group->getKey() == $this->defaultCustomerGroup)
                    ? "1"
                    : "0"),
                'customer_tax_class_key' => $group->getKey(),
            );
        }

        return $groups;
    }

    /**
     * returns allowed shipping and address countries
     *
     * @return array
     */
    public function getAllowedAddresses()
    {
        if ($this->allowedCountries !== null) {
            return $this->allowedCountries;
        }
        $this->allowedCountries = array();

        $allowedCountries = Shopware()->Modules()->Admin()->sGetCountryList();
        $stateTranslation = Shopware()->Modules()->Admin()->sGetCountryStateTranslation();

        foreach ($allowedCountries as $country) {
            $exportStates = array();

            // Get country states
            $states = Shopware()->Db()->fetchAssoc(
                "
                                      SELECT * FROM s_core_countries_states
                                      WHERE countryID = ?
                                      ORDER BY position, name ASC
                                      ",
                array($country['id'])
            );

            foreach ($states as $stateId => $state) {
                if (isset($stateTranslation[$stateId])) {
                    $state = array_merge($state, $stateTranslation[$stateId]);
                }

                if ($state['active'] == 1) {
                    $exportStates[] = $country['countryiso'] . '-' . $state['shortcode'];
                }
            }

            $exportStates = count($states) == count($exportStates)
                ? 'All'
                : $exportStates;

            $this->allowedCountries[] = array(
                'country' => $country['countryiso'],
                'state'   => $exportStates,
            );
        }

        return $this->allowedCountries;
    }

    /**
     * Returns all payment methods (active AND inactive)
     *
     * @return array
     */
    public function getAllPaymentMethods()
    {
        $returnPaymentMethods = array();
        $shopPaymentMethods   = Shopware()->Db()->fetchAll(
            "SELECT DISTINCT name FROM `s_core_paymentmeans` WHERE name != ''"
        );

        foreach ($shopPaymentMethods as $shopPaymentMethod) {
            $paymentMethod          = array(
                'id' => $shopPaymentMethod['name'],
            );
            $returnPaymentMethods[] = $paymentMethod;
        }

        return $returnPaymentMethods;
    }
}
