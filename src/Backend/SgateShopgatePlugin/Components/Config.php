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

require_once dirname(__FILE__) . '/../vendor/autoload.php';

/**
 * @property string $export_product_description
 * @property string $export_product_downloads
 */
class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config extends ShopgateConfig
{
    const REDIRECT_TYPE_HTTP                             = 'http';
    const REDIRECT_TYPE_JS                               = 'js';
    const INTERNAL_PLUGIN_NAME                           = 'SgateShopgatePlugin';
    const EXPORT_PRODUCT_DESCTIPTION_DESC                = 'desc';
    const EXPORT_PRODUCT_DESCTIPTION_SHORT_DESC          = 'short_desc';
    const EXPORT_PRODUCT_DESCTIPTION_DESC_AND_SHORT_DESC = 'desc_and_short_desc';
    const EXPORT_PRODUCT_DESCTIPTION_SHORT_DESC_AND_DESC = 'short_desc_and_desc';
    const EXPORT_PRODUCT_DOWNLOADS_NO                    = 'no';
    const EXPORT_PRODUCT_DOWNLOADS_ABOVE_DESCRIPTION     = 'above';
    const EXPORT_PRODUCT_DOWNLOADS_BELOW_DESCRIPTION     = 'below';
    /** shopgate config identifier */
    const HIDDEN_CONFIG_IDENTIFIER = 'SGATE_CONFIG';
    /** shipping type of shopgate order, in case shipping method is provided by check_cart */
    const SHIPPING_SERVICE_PLUGIN_API = 'PLUGINAPI';
    /** configuration form id for hidden fields */
    const HIDDEN_CONFIGURATION_FORM_ID = 0;

    protected $is_active;

    protected $fixed_shipping_service;

    protected $shipping_not_blocked_status;

    protected $send_order_mail;

    protected $redirect_type;

    /**
     * @var int
     */
    protected $redirect_index_blog_page;

    protected $export_attributes;

    protected $export_attributes_as_description;

    protected $export_dimension_unit;

    protected $root_category;

    /**
     * not allowed settings identifiers
     *
     * @var array
     */
    protected $set_settings_black_list = array(
        'enable_set_settings',
        'enable_get_settings',
    );

    public function startup()
    {
        $this->setEnablePing(true);
        $this->setEnableAddOrder(true);
        $this->setEnableUpdateOrder(true);
        $this->setEnableGetCustomer(true);
        $this->setEnableRegisterCustomer(true);
        $this->setEnableGetCategoriesCsv(true);
        $this->setEnableGetItemsCsv(true);
        $this->setEnableGetItems(true);
        $this->setEnableGetCategories(true);
        $this->setEnableGetReviewsCsv(true);
        $this->setEnableGetReviews(true);
        $this->setEnableCron(true);
        $this->setEnableCheckCart(true);
        $this->setEnableCheckStock(true);
        $this->setEnableRedeemCoupons(true);
        $this->setEnableGetLogFile(true);
        $this->setEnableClearLogFile(true);
        $this->setEnableClearCache(true);
        $this->setEnableRedirectKeywordUpdate(true);
        $this->setEnableMobileWebsite(true);
        $this->setEnableGetSettings(true);
        $this->setEnableSetSettings(true);
        $this->setEnableGetOrders(true);
        $this->setEnableDefaultRedirect(false);
        $this->setSupportedFieldsCheckCart(
            array(
                'external_coupons',
                'shipping_methods',
                'items',
                'payment_methods',
            )
        );
        $this->setSupportedFieldsGetSettings(
            array(
                'tax',
                'customer_groups',
                'allowed_address_countries',
                'allowed_shipping_countries',
                'payment_methods',
            )
        );
        $this->setRedirectableGetParams(array_merge($this->getRedirectableGetParams(), array('sPartner')));

        $shopwareDir = rtrim(Shopware()->DocPath(), DS) . DS;

        $shopgateHiddenConfiguration = $this->loadHiddenConfigurationValues();
        $shopgateConfiguration       = array_merge($shopgateHiddenConfiguration, $this->loadFormConfigurationValues());

        // add additional (fixed) settings (that are not saved into the database)
        $shopgateConfiguration['plugin_name']        = 'shopware4';
        $shopgateConfiguration['cache_folder_path']  = $shopwareDir . 'var' . DS . 'cache' . DS. 'shopgate' . DS;
        $shopgateConfiguration['log_folder_path']    = $shopwareDir . 'var' . DS . 'log' . DS . 'shopgate' . DS;
        $shopgateConfiguration['export_folder_path'] = $shopwareDir . 'files' . DS . 'shopgate' . DS;

        $this->loadArray($shopgateConfiguration);

        $this->createFolder($this->getExportFolderPath());
        $this->createFolder($this->getLogFolderPath());
        $this->createFolder($this->getCacheFolderPath());

        return true;
    }

    /**
     * @param string $folderName
     * @param int    $mode
     * @param bool   $recursive
     */
    private function createFolder($folderName, $mode = 0777, $recursive = true)
    {
        if (file_exists($folderName)) {
            return;
        }

        @mkdir($folderName, $mode, $recursive);
    }

    /**
     * @param array $settings
     */
    public function load(array $settings = null)
    {
        if (!empty($settings)) {
            $this->loadArray($settings);
        }
    }

    /**
     * @param array $fieldList
     * @param bool  $validate
     *
     * @throws ShopgateLibraryException
     */
    public function save(array $fieldList, $validate = true)
    {
        if (empty($fieldList)) {
            return;
        }

        $formConfigurationFields     = $this->getFormConfigurationMapping();
        $hiddenConfigurationValues   = $this->loadHiddenConfigurationValues();
        $currentConfigurationValues  = $this->toArray();
        $shopgateConfigurationFormId = $this->getShopgateConfigurationFormId();

        foreach ($fieldList as $configurationField) {
            if ($this->isConfigurationFieldBlacklisted($configurationField)) {
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::CONFIG_READ_WRITE_ERROR,
                    sprintf("Not allowed settings identifier: %s", $configurationField),
                    true
                );
            }

            if (isset($formConfigurationFields[$configurationField])) {
                $this->saveSettingToDb(
                    $shopgateConfigurationFormId,
                    Shopware()->Shop()->getId(),
                    $formConfigurationFields[$configurationField],
                    $currentConfigurationValues[$configurationField]
                );
            } else {
                $hiddenConfigurationValues[$configurationField] = $currentConfigurationValues[$configurationField];
            }
        }

        $this->saveHiddenConfigurationValues($hiddenConfigurationValues);
    }

    /**
     * @return int
     * @throws ShopgateLibraryException
     */
    protected function getShopgateConfigurationFormId()
    {
        $sql    = "
				SELECT
					`f`.`id`
				FROM
					`s_core_config_forms` AS `f`
					INNER JOIN `s_core_plugins` AS `p` ON(`f`.`plugin_id` = `p`.`id`)
				WHERE
					`p`.`name` LIKE '" . (self::INTERNAL_PLUGIN_NAME) . "'
			";
        $formId = Shopware()->Db()->fetchOne($sql);
        if (empty($formId)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DATABASE_ERROR,
                "No configuration form has been found, plugin is possibly not installed correctly.", true
            );
        }

        return (int)$formId;
    }

    /**
     * @param array $hiddenConfigurationValues
     *
     * @return bool
     */
    protected function saveHiddenConfigurationValues(array $hiddenConfigurationValues)
    {
        return $this->saveSettingToDb(
            self::HIDDEN_CONFIGURATION_FORM_ID,
            Shopware()->Shop()->getId(),
            self::HIDDEN_CONFIG_IDENTIFIER,
            base64_encode(serialize($hiddenConfigurationValues))
        );
    }

    /**
     * @return array
     */
    protected function loadHiddenConfigurationValues()
    {
        $shopgateConfiguration = array();
        foreach ($this->loadHiddenConfigurationValuesFromDatabase() as $key => $value) {
            if (!isset($this->{$key})) {
                continue;
            }

            $shopgateConfiguration[$key] = $value;
        }

        return $shopgateConfiguration;
    }

    /**
     * @return array
     */
    protected function loadHiddenConfigurationValuesFromDatabase()
    {
        if (!$this->isResourceLoaded('Shop')) {
            return array();
        }

        $sql                = "
                SELECT 
                    `s_core_config_values`.`value`
                FROM `s_core_config_elements` 
                    JOIN `s_core_config_values` ON `s_core_config_values`.`element_id` = `s_core_config_elements`.`id` 
                WHERE `s_core_config_elements`.`form_id` = '" . self::HIDDEN_CONFIGURATION_FORM_ID . "' 
                    AND `s_core_config_elements`.`name` = '" . self::HIDDEN_CONFIG_IDENTIFIER . "'
                    AND `s_core_config_values`.`shop_id` = '" . Shopware()->Shop()->getId() . "'
            ";
        $configurationValue = Shopware()->Db()->fetchOne($sql);

        $configurationValues = array();
        if (!empty($configurationValue)) {
            $configurationValues = unserialize(base64_decode(unserialize($configurationValue)));
        }

        return $configurationValues;
    }

    /**
     * @return array
     */
    protected function loadFormConfigurationValues()
    {
        /** @var Enlight_Config $pConfig */
        $pConfig = Shopware()->Plugins()->Backend()->SgateShopgatePlugin()->Config();

        // since Shopware 5.6 for some setups the plugin configuration can't be loaded anymore the old way
        if (empty($pConfig)) {
            return $this->loadFormConfigurationValuesNew();
        }

        $shopgateConfig = array();
        foreach ($this->getFormConfigurationMapping() as $setting => $shopwareKey) {
            $value = $pConfig->{$shopwareKey};
            if (!is_null($value)) {
                $shopgateConfig[$setting] = $value;
            }
        }

        return $shopgateConfig;
    }

    /**
     * @return array
     */
    protected function loadFormConfigurationValuesNew()
    {
        $shopgateConfig = array();
        foreach ($this->getFormConfigurationMapping() as $setting => $shopwareKey) {
            $value = Shopware()->Config()->getByNamespace('SgateShopgatePlugin', $shopwareKey);
            if (!is_null($value)) {
                $shopgateConfig[$setting] = $value;
            }
        }

        return $shopgateConfig;
    }

    /**
     * @param string $saveField
     *
     * @return bool
     */
    protected function isConfigurationFieldBlacklisted($saveField)
    {
        return in_array($saveField, $this->set_settings_black_list);
    }

    /**
     * @param int    $formId
     * @param int    $subShopId
     * @param string $elementName
     * @param string $valueData
     *
     * @return bool
     */
    private function saveSettingToDb($formId, $subShopId, $elementName, $valueData)
    {
        /** @var $element Shopware\Models\Config\Element */
        $element = Shopware()->Models()
                             ->getRepository('Shopware\Models\Config\Element')
                             ->findOneBy(array("name" => $elementName, "form" => $formId));

        // element must exist
        if (empty($element)) {
            return false;
        }

        // "required" elements may not be empty
        if (empty($valueData) && $element->getRequired()) {
            return false;
        }

        // cast the valueData to the correct type, as if it was saved by the config-form (take over the default value to the config-value if it's not null)
        if (gettype($element->getValue()) !== null) {
            settype($valueData, gettype($element->getValue()));
        }

        // find the specific setting for later use
        /** @var $updateValue Shopware\Models\Config\Value */
        $updateValue = null;
        /** @var $value Shopware\Models\Config\Value */
        foreach ($element->getValues() as $value) {
            if ($value->getShop()->getId() == $subShopId) {
                if ($value->getValue() == $valueData) {
                    // nothing to do since nothing is changing
                    return true;
                }
                $updateValue = $value;
                break;
            }
        }

        // do not save a default value-setting as additional value entry
        if ($element->getValue() == $valueData) {
            if (!empty($updateValue)) {
                // the value has been changed to default -> simply remove it
                Shopware()->Models()->remove($updateValue);
            }
        } else {
            // create a ne value element if none exists, yet
            if (empty($updateValue)) {
                $updateValue = new Shopware\Models\Config\Value();
                $updateValue->setElement($element);
                $updateValue->setShop(Shopware()->Models()->find('Shopware\Models\Shop\Shop', $subShopId));
            }
            // set the new value
            $updateValue->setValue($valueData);
            Shopware()->Models()->persist($updateValue);
        }
        Shopware()->Models()->flush();

        return true;
    }

    /**
     * @param $subshop
     */
    public function reloadBySubShop($subshop)
    {
        // load config data in the same manner as shopware4 does
        $sql    = "
			SELECT
				ce.name,
				IFNULL(IFNULL(cv.value, cm.value), ce.value) as value
			FROM s_core_plugins p
				JOIN s_core_config_forms cf ON cf.plugin_id = p.id
				JOIN s_core_config_elements ce ON ce.form_id = cf.id
				LEFT JOIN s_core_config_values cv ON cv.element_id = ce.id AND cv.shop_id = ?
				LEFT JOIN s_core_config_values cm ON cm.element_id = ce.id AND cm.shop_id = ?
			WHERE p.name=?
		";
        $config = Shopware()->Db()->fetchPairs(
            $sql,
            array(
                $subshop !== null
                    ? $subshop->getId()
                    : null,
                $subshop !== null && $subshop->getMain() !== null
                    ? $subshop->getMain()->getId()
                    : 1,
                self::INTERNAL_PLUGIN_NAME,
            )
        );
        foreach ($config as $key => $value) {
            $config[$key] = unserialize($value);
        }

        // prepare data to store into config object
        $settingMap = $this->getFormConfigurationMapping();
        $shopwareDir   = rtrim(Shopware()->DocPath(), DS) . DS;

        // create config-array from configuration forms
        $shopgate_config = array();
        foreach ($settingMap as $setting => $shopwareKey) {
            $shopgate_config[$setting] = $config[$shopwareKey];
        }

        // add additional (fixed) settings (that are not saved into the database)
        $shopgate_config['plugin_name']        = 'shopware4';
        $shopgate_config['cache_folder_path']  = $shopwareDir . 'var' . DS . 'cache' . DS . 'shopgate' . DS;
        $shopgate_config['log_folder_path']    = $shopwareDir . 'var' . DS . 'log' . DS . 'shopgate' . DS;
        $shopgate_config['export_folder_path'] = $shopwareDir . 'files' . DS . 'shopgate' . DS;

        // overwrite local object data by settings from subshop that owns the order
        $this->loadArray($shopgate_config);
    }

    /**
     * @return array
     */
    protected function getFormConfigurationMapping()
    {
        return array(
            'apikey'                           => 'SGATE_API_KEY',
            'shop_number'                      => 'SGATE_SHOP_NUMBER',
            'customer_number'                  => 'SGATE_CUSTOMER_NUMBER',
            'server'                           => 'SGATE_SERVER',
            'api_url'                          => 'SGATE_SERVER_URL',
            'cname'                            => 'SGATE_CNAME',
            'alias'                            => 'SGATE_ALIAS',
            'is_active'                        => 'SGATE_IS_ACTIVE',
            'redirect_type'                    => 'SGATE_REDIRECT_TYPE',
            'redirect_index_blog_page'         => 'SGATE_REDIRECT_INDEX_BLOG',
            'send_order_mail'                  => 'SGATE_SEND_ORDER_MAIL',
            'shipping_not_blocked_status'      => 'SGATE_STATUS_SHIPPING_NOT_BLOCKED',
            'export_attributes'                => 'SGATE_EXPORT_ATTRIBUTES',
            'export_attributes_as_description' => 'SGATE_EXPORT_ATTRIBUTES_AS_DESCRIPTION',
            'export_dimension_unit'            => 'SGATE_EXPORT_DIMENSION_UNIT',
            'fixed_shipping_service'           => 'SGATE_FIXED_SHIPPING_SERVICE',
            'export_product_description'       => 'SGATE_EXPORT_PRODUCT_DESCRIPTION',
            'export_product_downloads'         => 'SGATE_EXPORT_PRODUCT_DOWNLOADS',
            'root_category'                    => 'SGATE_ROOT_CATEGORY',
        );
    }

    /**
     * Wrapper method for static mapping array of non backend configurable values
     *
     * @return array
     */
    public static function getHiddenConfigurationMapping()
    {
        // return a mapping to real shopgate config keys that can be transmitted via set_settings
        // all hidden configs are saved as serialized and encoded string in a shopware form element with
        // a key that is determined by self::HIDDEN_CONFIG_IDENTIFIER
        return array(
            'shop_is_active'                 => 'SGATE_SHOP_IS_ACTIVE',
            'enable_ping'                    => 'SGATE_ENABLE_PING',
            'enable_add_order'               => 'SGATE_ENABLE_ADD_ORDER',
            'enable_update_order'            => 'SGATE_ENABLE_UPDATE_ORDER',
            'enable_get_customer'            => 'SGATE_ENABLE_GET_CUSTOMER',
            'enable_register_customer'       => 'SGATE_ENABLE_REGISTER_CUSTOMER',
            'enable_get_categories_csv'      => 'SGATE_ENABLE_GET_CATEGORIES_CSV',
            'enable_get_items_csv'           => 'SGATE_ENABLE_GET_ITEMS_CSV',
            'enable_get_items'               => 'SGATE_ENABLE_GET_ITEMS',
            'enable_get_categories'          => 'SGATE_ENABLE_GET_CATEGORIES',
            'enable_get_reviews_csv'         => 'SGATE_ENABLE_GET_REVIEWS_CSV',
            'enable_get_reviews'             => 'SGATE_ENABLE_GET_REVIEWS',
            'enable_cron'                    => 'SGATE_ENABLE_CRON',
            'enable_check_cart'              => 'SGATE_ENABLE_CHECK_CART',
            'enable_check_stock'             => 'SGATE_ENABLE_CHECK_STOCK',
            'enable_redeem_coupons'          => 'SGATE_ENABLE_REDEEM_COUPONS',
            'enable_get_log_file'            => 'SGATE_ENABLE_GET_LOG_FILE',
            'enable_clear_log_file'          => 'SGATE_ENABLE_CLEAR_LOG_FILE',
            'enable_clear_cache'             => 'SGATE_ENABLE_CLEAR_CACHE',
            'enable_redirect_keyword_update' => 'SGATE_ENABLE_REDIRECT_KEYWORD_UPDATE',
            'enable_mobile_website'          => 'SGATE_ENABLE_MOBILE_WEBSITE',
            'enable_get_orders'              => 'SGATE_ENABLE_GET_ORDERS',
            'enable_default_redirect'        => 'SGATE_DEFAULT_REDIRECT',
        );
    }

    /**
     * @return array
     */
    protected function getConfigurationMapping()
    {
        return array_merge($this->getFormConfigurationMapping(), $this->getHiddenConfigurationMapping());
    }

    /**
     * @return bool
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * @param bool $value
     */
    public function setIsActive($value)
    {
        $this->is_active = $value;
    }

    public function getFixedShippingService()
    {
        return $this->fixed_shipping_service;
    }

    public function setFixedShippingService($value)
    {
        $this->fixed_shipping_service = $value;
    }

    public function getShippingNotBlockedStatus()
    {
        return $this->shipping_not_blocked_status;
    }

    public function setShippingNotBlockedStatus($value)
    {
        $this->shipping_not_blocked_status = $value;
    }

    /**
     * @return bool
     */
    public function getSendOrderMail()
    {
        return $this->send_order_mail;
    }

    /**
     * @param bool $value
     */
    public function setSendOrderMail($value)
    {
        $this->send_order_mail = $value;
    }

    /**
     * @return string http|js
     */
    public function getRedirectType()
    {
        return $this->redirect_type;
    }

    /**
     * @param string $value http|js
     */
    public function setRedirectType($value)
    {
        $this->redirect_type = $value;
    }

    /**
     * @return array
     */
    public function getExportAttributes()
    {
        /** @var Enlight_Config|array $value */
        $value = $this->export_attributes;

        return is_object($value)
            ? $value->toArray()
            : $value;
    }

    /**
     * @param array $value
     */
    public function setExportAttributes($value)
    {
        if (is_array($value)) {
            $value = implode(",", $value);
        }
        $this->export_attributes = $value;
    }

    /**
     * @return array
     */
    public function getExportAttributesAsDescription()
    {
        /** @var Enlight_Config|array $value */
        $value = $this->export_attributes_as_description;

        return is_object($value)
            ? $value->toArray()
            : $value;
    }

    /**
     *
     * @param array $value
     */
    public function setExportAttributesAsDescription($value)
    {
        if (is_array($value)) {
            $value = implode(",", $value);
        }
        $this->export_attributes_as_description = $value;
    }

    /**
     * @return string
     */
    public function getExportDimensionUnit()
    {
        return $this->export_dimension_unit;
    }

    /**
     * @param string $value
     */
    public function setExportDimensionUnit($value)
    {
        $this->export_dimension_unit = $value;
    }

    /**
     * @return string
     */
    public function getExportProductDescription()
    {
        return $this->export_product_description;
    }

    /**
     * @param string $value
     */
    public function setExportProductDescription($value)
    {
        $this->export_product_description = $value;
    }

    /**
     * @return string
     */
    public function getExportProductDownloads()
    {
        return $this->export_product_downloads;
    }

    /**
     *
     * @param string $value
     */
    public function setExportProductDownloads($value)
    {
        $this->export_product_downloads = $value;
    }

    /**
     *
     * @return int
     */
    public function getRootCategory()
    {
        return $this->root_category;
    }

    /**
     *
     * @param int $value
     */
    public function setRedirectIndexBlogPage($value)
    {
        $this->redirect_index_blog_page = $value;
    }

    /**
     *
     * @return int
     */
    public function getRedirectIndexBlogPage()
    {
        return $this->redirect_index_blog_page;
    }

    /**
     *
     * @param int $value
     */
    public function setRootCategory($value)
    {
        $this->root_category = $value;
    }

    /**
     * @param string $moduleName
     *
     * @return bool
     */
    public function isModuleEnabled($moduleName)
    {
        $moduleId = Shopware()->Db()->fetchOne(
            "SELECT id FROM s_core_plugins WHERE name=? AND active=1",
            array(
                $moduleName,
            )
        );

        if (!empty($moduleId)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a resource is already loaded by the DI container
     *
     * @param string $resourceName
     *
     * @return bool
     */
    public function isResourceLoaded($resourceName)
    {
        $resourceLoaded = $this->assertMinimumVersion('5.2.0')
            ? Shopware()->Container()->initialized($resourceName)
            : Shopware()->Bootstrap()->issetResource($resourceName);

        return $resourceLoaded;
    }

    /**
     * @param string $requiredVersion The minimum version Shopware should be
     *
     * @return bool true if the installed Shopware version is greater or equal to $version or the Shopware version
     *              constant is undefined; false otherwise
     */
    public function assertMinimumVersion($requiredVersion = '4.0.0')
    {
        $version = Shopware()->Config()->version;

        return (
            $version === '___VERSION___'
            || version_compare($version, $requiredVersion, '>=')
        );
    }
}
