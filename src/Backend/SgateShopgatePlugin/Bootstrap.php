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

require_once __DIR__ . '/Components/CSRFWhitelistAware.php';
require_once __DIR__ . '/Helpers/Attribute.php';
require_once __DIR__ . '/Helpers/Cart.php';
require_once __DIR__ . '/Helpers/FormElementSelect.php';
require_once __DIR__ . '/Helpers/FormElementCheckbox.php';
require_once __DIR__ . '/Helpers/FormElementText.php';
require_once __DIR__ . '/Helpers/FormElementTextfield.php';
require_once dirname(__FILE__) . '/Plugin.php';

use Shopgate\Helpers\Attribute as AttributeHelper;
use Shopgate\Helpers\FormElement;
use Shopgate\Helpers\FormElementCheckbox;
use Shopgate\Helpers\FormElementOptionsContainerCheckbox;
use Shopgate\Helpers\FormElementOptionsContainerSelect;
use Shopgate\Helpers\FormElementOptionsContainerText;
use Shopgate\Helpers\FormElementOptionsContainerTextfield;
use Shopgate\Helpers\FormElementSelect;
use Shopgate\Helpers\FormElementText;
use Shopgate\Helpers\FormElementTextfield;
use Shopware\Models\Category\Category;
use Shopware\Models\Config\Form;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Status;
use Shopware\Models\Shop\Shop;
use Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config as ShopwareShopgatePluginConfig;

class Shopware_Plugins_Backend_SgateShopgatePlugin_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /** @var bool */
    private $orderStatusUseGetDescription;

    ############################################################################
    ## Public actions
    ## Doku: http://wiki.shopware.de/Plugin-Grundgeruest_detail_887.html#Bootstrap
    ############################################################################

    /**
     * Install Shopgate-Plugin
     *
     * @return bool
     */
    public function install()
    {
        $config = new ShopwareShopgatePluginConfig();
        if (!$config->assertMinimumVersion()) {
            return false;
        }

        $this->updateEvents();

        // create or update shopgate settings form and hidden configuration
        $this->setupConfigStorage();

        // shopgate_orders table installation
        $this->sqlInstall();

        // Updates possibly existing database tables to current structure
        $this->sqlUpdate('2.2.0');

        // create shopgate paymentmeans (payment methods)
        $this->paymentInstall();

        // register shop in shopgate system
        $this->registerSystem();

        $this->installCache();

        return true;
    }

    public function update($oldVersion)
    {
        $this->setupConfigStorage();

        $this->sqlUpdate($oldVersion);

        $this->updateEvents();

        return true;
    }

    /**
     * Uninstall Shopgate-Plugin
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->deleteHiddenConfig();
        $this->paymentUninstall();

        return true;
    }

    public function afterInit()
    {
        $this->registerCustomModels();
    }

    /**
     * Load plugin meta information
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version'     => $this->getVersion(),
            'autor'       => 'Shopgate GmbH',
            'copyright'   => 'Copyright @ 2017, Shopgate GmbH',
            'label'       => $this->getLabel(),
            'source'      => '',
            'description' => 'Shopgate Schnittstelle - Shopware 4 & 5',
            'licence'     => 'free',
            'support'     => 'technik@shopgate.com',
            'link'        => 'http://www.shopgate.com',
            'changes'     => '-',
            'revision'    => '-',
        );
    }

    public function getVersion()
    {
        return "2.9.80";
    }

    public function getLabel()
    {
        return 'Shopgate - Mobile Shopping';
    }


    ############################################################################
    ## Install Helper
    ############################################################################

    private function installCache()
    {
        $cacheDir = Shopware()->DocPath() . DS . 'cache' . DS;

        $dirs = array(
            'shopgate' . DS . 'cache',
            'shopgate' . DS . 'export',
            'shopgate' . DS . 'log',
        );

        foreach ($dirs as $dir) {
            $path = $cacheDir . $dir;
            @mkdir($path, 0777, true);
        }
    }

    /**
     * Creates database table(s)
     *
     * @return bool
     */
    protected function sqlInstall()
    {
        Shopware()->Db()->query(
            "CREATE TABLE IF NOT EXISTS `s_shopgate_orders` (
                    `id` INT NOT NULL auto_increment,
                    `orderID` INT NOT NULL,
                    `shopgate_order_number` BIGINT NOT NULL,
                    `is_shipping_blocked` int(1) NOT NULL DEFAULT 1,
                    `is_sent_to_shopgate` int(1) NOT NULL DEFAULT 0,
                    `is_cancellation_sent_to_shopgate` int(1) NOT NULL DEFAULT 0,
                    `reported_cancellations` text,
                    `received_data` text,
                    `order_item_map` text,
                    PRIMARY KEY (`id`)
                ) ENGINE = MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
        );

        return true;
    }

    protected function sqlUpdate($oldVersion)
    {
        $result         = Shopware()->Db()->fetchAll("SHOW COLUMNS FROM `s_shopgate_orders`");
        $existingFields = array();
        foreach ($result as $row) {
            $existingFields[$row['Field']] = true;
        }
        if (empty($existingFields['is_shipping_blocked'])) {
            Shopware()->Db()->query(
                "ALTER TABLE `s_shopgate_orders` ADD COLUMN `is_shipping_blocked` int(1) NOT NULL DEFAULT 1;"
            );
        }
        if (empty($existingFields['is_cancellation_sent_to_shopgate'])) {
            Shopware()->Db()->query(
                "ALTER TABLE `s_shopgate_orders` ADD COLUMN `is_cancellation_sent_to_shopgate` int(1) NOT NULL DEFAULT 0;"
            );
        }
        if (empty($existingFields['reported_cancellations'])) {
            Shopware()->Db()->query("ALTER TABLE `s_shopgate_orders` ADD COLUMN `reported_cancellations` text;");
        }
        if (empty($existingFields['received_data'])) {
            Shopware()->Db()->query("ALTER TABLE `s_shopgate_orders` ADD COLUMN `received_data` text;");
        }
        if (empty($existingFields['order_item_map'])) {
            Shopware()->Db()->query("ALTER TABLE `s_shopgate_orders` ADD COLUMN `order_item_map` text;");
        }

        // Update some fields if they are already there (may change names as well)
        // -> shopgate_order_id => id
        if (!empty($existingFields['shopgate_order_id'])) {
            Shopware()->Db()->query(
                "ALTER TABLE `s_shopgate_orders` CHANGE COLUMN `shopgate_order_id` `id` INT NOT NULL auto_increment;"
            );
            unset($existingFields['shopgate_order_id']);
            $existingFields['id'] = true;
        }
        if (empty($existingFields['id'])) {
            Shopware()->Db()->query(
                "ALTER TABLE `s_shopgate_orders` ADD COLUMN `id` INT NOT NULL auto_increment PRIMARY KEY FIRST;"
            );
        }
        // -> shopgate_order_number => shopgate_order_number
        if (!empty($existingFields['shopgate_order_number'])) {
            Shopware()->Db()->query(
                "ALTER TABLE `s_shopgate_orders` CHANGE COLUMN `shopgate_order_number` `shopgate_order_number` VARCHAR( 20 ) NOT NULL;"
            );
        }
        if (empty($existingFields['shopgate_order_number'])) {
            Shopware()->Db()->query(
                "ALTER TABLE `s_shopgate_orders` ADD COLUMN `shopgate_order_number` VARCHAR( 20 ) NOT NULL;"
            );
        }
        // -> shipped => is_sent_to_shopgate
        if (!empty($existingFields['shipped'])) {
            Shopware()->Db()->query(
                "ALTER TABLE `s_shopgate_orders` CHANGE COLUMN `shipped` `is_sent_to_shopgate` int(1) NOT NULL DEFAULT 0;"
            );
            unset($existingFields['shipped']);
            $existingFields['is_sent_to_shopgate'] = true;
        }
        if (empty($existingFields['is_sent_to_shopgate'])) {
            Shopware()->Db()->query(
                "ALTER TABLE `s_shopgate_orders` ADD COLUMN `is_sent_to_shopgate` int(1) NOT NULL DEFAULT 0;"
            );
        }

        // Drop innecessary fields
        if (!empty($existingFields['confirm_link'])) {
            Shopware()->Db()->query("ALTER TABLE `s_shopgate_orders` DROP COLUMN `confirm_link`;");
            unset($existingFields['confirm_link']);
        }

        if (version_compare($oldVersion, "2.9.3", "<=")) {
            Shopware()->Db()->query(
                "CREATE TABLE IF NOT EXISTS `s_shopgate_customer` (
                    `id` INT NOT NULL auto_increment,
                    `userID` INT(11) NOT NULL,
                    `token` varchar(100) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE = MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
            );
        }
    }

    /**
     * Installs paymentmean shopgate
     *
     * @return bool
     */
    protected function paymentInstall()
    {
        // create a structure of paymentmeans that need to be present or inserted if not
        $aPayments = array(
            'shopgate'       => array(
                "name"        => "shopgate",
                "description" => "Shopgate Zahlungsart - Der Zahlungsfluss l&auml;uft &uuml;ber das Shopgate-Konto",
                "template"    => "prepayment.tpl",
                "active"      => 0,
                "hide"        => 1,
            ),
            'mobile_payment' => array(
                "name"        => "mobile_payment",
                "description" => "Generische Zahlungsart von Shopgate - Greift, wenn keine passende Zahlungsart gefunden wurde",
                "template"    => "prepayment.tpl",
                "active"      => 0,
                "hide"        => 1,
            ),
        );

        // filter all paymentmeans that are already installed
        $installedPayments = Shopware()->Db()->fetchAll(
            "SELECT DISTINCT name FROM `s_core_paymentmeans` WHERE name='shopgate' OR name ='mobile_payment'"
        );
        if (!empty($installedPayments) && is_array($installedPayments)) {
            foreach ($installedPayments as $payment) {
                if (array_key_exists($payment['name'], $aPayments)) {
                    unset($aPayments[$payment['name']]);
                }
            }
        }

        // insert all remaining payment
        foreach ($aPayments as $aPayment) {
            Shopware()->Db()->insert("s_core_paymentmeans", $aPayment);
        }

        return true;
    }

    /**
     * Uninstalls paymentmean shopgate
     *
     * @return bool
     */
    protected function paymentUninstall()
    {
        // Do not uninstall since this means all importeds orders whould have a wrong "payment type" displayed
        // 		Shopware()->Db()->query("DELETE FROM s_core_paymentmeans WHERE name = 'shopgate'");
        // 		Shopware()->Db()->query("DELETE FROM s_core_paymentmeans WHERE name = 'mobile_payment'");

        return true;
    }

    protected function updateEvents()
    {
        Shopware()->Db()->query("DELETE FROM `s_core_subscribes` WHERE `pluginID`=?", array($this->getId()));

        /*** frontend event -> framework-api access ***/
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Shopgate',
            'onGetControllerPathFrontend'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Order',
            'onBackendAfterSaveOrder'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_Backend_Order_Delete',
            'onBackendDeleteOrder'
        );

        /*** mobile redirect ***/
        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopShutdown',
            'mobileRedirect'
        );

        $this->subscribeEvent(
            'Shopware_Modules_Order_SendMail_Send',
            'sendOrderMailForShopgateOrders',
            450
        );

        /* insert javascript for web checkout */
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
            'onFrontendCheckout'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Register',
            'onFrontendRegister'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Account',
            'onFrontendAccount'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Custom',
            'onFrontendCustom'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_Frontend_Account_Password',
            'onFrontendPassword'
        );
    }

    /**
     * Installs a form and all form elements that are required, including hidden configuration
     * that is not assigned to any specific form
     */
    protected function setupConfigStorage()
    {
        // get plugin config form
        $form = $this->Form();

        $formElements = $this->getFormElements();

        $installedFormElements = array();
        foreach ($formElements as $formElement) {
            // take only the name from just created/updated form element, for later processing
            $installedFormElements[] = $this->setupConfigFormElement($form, $formElement)->getName();
        }

        $this->removeOutdatedConfigFormElements($form, $installedFormElements);

        // add translation
        $translation = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Translation();
        $translation->applyTranslations($form);

        // write out configuration to work with it (form save method is deprecated and doesn't do anything)
        $this->Application()->Models()->persist($form);
        $this->Application()->Models()->flush();

        // all form elements are visible by default, so in order to hide them, they will be detached from the form
        $this->detachHiddenConfigFormElements($formElements);
    }

    /**
     * @param Form        $form
     * @param FormElement $formElement
     * @param int         $position
     *
     * @return \Shopware\Models\Config\Element
     */
    protected function setupConfigFormElement(Form $form, FormElement $formElement, $position = null)
    {
        // Hidden and visible elements are stored in different locations (different forms)
        if (!$formElement->isVisibile()) {
            $hiddenElement = $this->loadHiddenConfigFormElement($formElement->getKey());
            if ($hiddenElement) {
                // Update element data if it's already existing
                $hiddenElement->setValue($formElement->getValue())
                    ->setLabel($formElement->getLabel())
                    ->setDescription($formElement->getDescription())
                    ->setType($formElement->getType())
                    ->setRequired($formElement->getRequired())
                    ->setPosition(
                        isset($position)
                            ? $position
                            : $formElement->getPosition()
                    )
                    ->setScope($formElement->getScope());

                // perform update
                $this->Application()->Models()->persist($hiddenElement);

                return $hiddenElement;
            }
        }

        // insert or update form element (elements with visibility status "invisible" will be detached later)
        // -> Shopware updates a form element automatically if the key already exists in the form
        $form->setElement($formElement->getType(), $formElement->getKey(), $formElement->getOptions());

        return $form->getElement($formElement->getKey());
    }

    /**
     * @param string $key
     *
     * @return \Shopware\Models\Config\Element|null
     */
    protected function loadHiddenConfigFormElement($key)
    {
        if (empty($key)) {
            return null;
        }

        $result = $this->Application()->Models()->getRepository('\Shopware\Models\Config\Element')->findBy(
            array(
                'form' => 0,
                'name' => $key,
            )
        );

        return isset($result[0])
            ? $result[0]
            : null;
    }

    /**
     * @param Form     $form
     * @param string[] $installedFormElements
     */
    protected function removeOutdatedConfigFormElements(Form $form, array $installedFormElements)
    {
        // there is only one hidden config form element, so there is no cleanup needed for that
        $installedFormElements = array_flip($installedFormElements);
        $em                    = $this->Application()->Models();
        foreach ($form->getElements() as $el) {
            // removes the form element if it was not part of the setup (or update)
            if (!isset($installedFormElements[$el->getName()])) {
                $em->remove($el);
            }
        }

        $em->flush();
    }

    /**
     * @return FormElement[]
     */
    protected function getFormElements()
    {
        $defaultShop   = $this->getDefaultShop();
        $categories    = $this->getShopCategories();
        $orderStatuses = $this->getOrderStatuses();
        $dispatchers   = $this->getDispatchers();

        return $this->getFormElementStructure($defaultShop, $categories, $orderStatuses, $dispatchers);
    }

    /**
     * @return array
     */
    protected function getOrderStatuses()
    {
        /** @var Status[] $shopwareOrderStatuses */
        $dql                   = "SELECT s FROM \\Shopware\\Models\\Order\\Status s WHERE s.group = 'state' ORDER BY s.position";
        $shopwareOrderStatuses = Shopware()->Models()->createQuery($dql)->getResult();

        $orderStatuses = array();
        foreach ($shopwareOrderStatuses as $shopwareOrderStatus) {
            $orderStatuses[] = array(
                $shopwareOrderStatus->getId(),
                $this->getOrderStatusName($shopwareOrderStatus),
            );
        }

        return $orderStatuses;
    }

    /**
     * @return array
     */
    protected function getDispatchers()
    {
        /** @var Dispatch[] $_dispatchers */
        $dql          = "SELECT d FROM \\Shopware\\Models\\Dispatch\\Dispatch d ORDER BY d.position";
        $_dispatchers = Shopware()->Models()->createQuery($dql)->getResult();

        $dispatchers = array();
        foreach ($_dispatchers as $_dispatcher) {
            $dispatchers[] = array($_dispatcher->getId(), $_dispatcher->getName());
        }

        return $dispatchers;
    }

    /**
     * @return array
     */
    protected function getShopCategories()
    {
        /** @var Category[] $category */
        $dql      = "SELECT c FROM \\Shopware\\Models\\Category\\Category c WHERE c.parent IS NULL";
        $category = Shopware()->Models()->createQuery($dql)->getResult();

        /** @var Category[] $allCategories */
        $allCategories = $this->getSubCategories($category[0], 0, 2);

        $categories = array();
        foreach ($allCategories as $category) {
            $categories[] = array($category->getId(), $category->getName());
        }

        return $categories;
    }

    /**
     * @return Shop
     */
    protected function getDefaultShop()
    {
        /** @var Shopware\Models\Shop\Shop[] $shops */
        $dql   = "SELECT s FROM \\Shopware\\Models\\Shop\\Shop s WHERE s.mainId IS NULL";
        $shops = Shopware()->Models()->createQuery($dql)->getResult();

        return $shops[0];
    }

    /**
     * @param Shop       $defaultShop
     * @param Category[] $categories
     * @param Status[]   $orderStatuses
     * @param Dispatch[] $dispatchers
     *
     * @return FormElement[]
     */
    protected function getFormElementStructure(
        Shop $defaultShop,
        array $categories,
        array $orderStatuses,
        array $dispatchers
    ) {
        $formElements = array();
        $position     = 0;

        $formElementCheckbox                 = new FormElementCheckbox();
        $formElementOptionsContainerCheckbox = new FormElementOptionsContainerCheckbox();
        $formElements[]                      = $formElementCheckbox
            ->setKey('SGATE_IS_ACTIVE')
            ->setOptions(
                $formElementOptionsContainerCheckbox
                    ->setLabel('Aktiv')
                    ->setValue(false)
                    ->setPosition($position++)
            );

        $formElementText                 = new FormElementText();
        $formElementOptionsContainerText = new FormElementOptionsContainerText();
        $formElements[]                  = $formElementText
            ->setKey('SGATE_CUSTOMER_NUMBER')
            ->setOptions(
                $formElementOptionsContainerText
                    ->setLabel('Ihre Kundennummer')
                    ->setValue('')
                    ->setRequired(true)
                    ->setPosition($position++)
            );

        $formElementText                 = new FormElementText();
        $formElementOptionsContainerText = new FormElementOptionsContainerText();
        $formElements[]                  = $formElementText
            ->setKey('SGATE_SHOP_NUMBER')
            ->setOptions(
                $formElementOptionsContainerText
                    ->setLabel('Ihre Shopnummer')
                    ->setValue('')
                    ->setRequired(true)
                    ->setPosition($position++)
            );

        $formElementText                 = new FormElementText();
        $formElementOptionsContainerText = new FormElementOptionsContainerText();
        $formElements[]                  = $formElementText
            ->setKey('SGATE_API_KEY')
            ->setOptions(
                $formElementOptionsContainerText
                    ->setLabel('Ihr API-Key')
                    ->setValue('')
                    ->setRequired(true)
                    ->setPosition($position++)
            );

        $formElementText                 = new FormElementText();
        $formElementOptionsContainerText = new FormElementOptionsContainerText();
        $formElements[]                  = $formElementText
            ->setKey('SGATE_ALIAS')
            ->setOptions(
                $formElementOptionsContainerText
                    ->setLabel('Shopgate Alias')
                    ->setValue('')
                    ->setPosition($position++)
            );

        $formElementText                 = new FormElementText();
        $formElementOptionsContainerText = new FormElementOptionsContainerText();
        $formElements[]                  = $formElementText
            ->setKey('SGATE_CNAME')
            ->setOptions(
                $formElementOptionsContainerText
                    ->setLabel('Eingetragener CNAME')
                    ->setValue('')
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_ROOT_CATEGORY')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Hauptkategorie des Shops')
                    ->setDescription('Wählen Sie hier die Wurzelkategorie Ihres Shops')
                    ->setDefaultValue($defaultShop->getCategory()->getId())
                    ->setSelectionValues($categories)
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_REDIRECT_TYPE')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Art der Weiterleitung')
                    ->setDescription(
                        'Verwenden Sie die JavaScript Weiterleitung nur, ' .
                        'wenn sie Cache-Mechanismen (z.B. Varnish-Cash) einsetzen.'
                    )
                    ->setDefaultValue(ShopwareShopgatePluginConfig::REDIRECT_TYPE_HTTP)
                    ->setSelectionValues(
                        array(
                            array(ShopwareShopgatePluginConfig::REDIRECT_TYPE_HTTP, 'HTTP (Empfohlen)'),
                            array(ShopwareShopgatePluginConfig::REDIRECT_TYPE_JS, 'JavaScript'),
                        )
                    )
                    ->setPosition($position++)
            );

        $formElementCheckbox                 = new FormElementCheckbox();
        $formElementOptionsContainerCheckbox = new FormElementOptionsContainerCheckbox();
        $formElements[]                      = $formElementCheckbox
            ->setKey('SGATE_REDIRECT_INDEX_BLOG')
            ->setOptions(
                $formElementOptionsContainerCheckbox
                    ->setLabel('Startseite weiterleiten wenn es eine Blogseite ist')
                    ->setValue(false)
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_STATUS_SHIPPING_NOT_BLOCKED')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Bestellstatus, wenn Versand nicht blockiert:')
                    ->setDefaultValue($orderStatuses[0])
                    ->setSelectionValues($orderStatuses)
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_FIXED_SHIPPING_SERVICE')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Feste Versandart für Bestellungen')
                    ->setDescription(
                        'Diese wird verwendet bei nicht m&ouml;glicher Zuordnung der importierten Versandart.'
                    )
                    ->setDefaultValue(9)
                    ->setSelectionValues($dispatchers)
                    ->setPosition($position++)
            );

        $formElementCheckbox                 = new FormElementCheckbox();
        $formElementOptionsContainerCheckbox = new FormElementOptionsContainerCheckbox();
        $formElements[]                      = $formElementCheckbox
            ->setKey('SGATE_SEND_ORDER_MAIL')
            ->setOptions(
                $formElementOptionsContainerCheckbox
                    ->setLabel('Bei neuer Bestellungen eine eMail an den Kunden senden')
                    ->setValue(false)
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_EXPORT_PRODUCT_DESCRIPTION')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Aufbau der Artikelbeschreibungen')
                    ->setDescription(
                        'Legt fest welches Feld für die Artikelbeschreibung beim Export genutzt werden soll'
                    )
                    ->setDefaultValue(ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DESCTIPTION_DESC)
                    ->setSelectionValues(
                        array(
                            array(
                                ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DESCTIPTION_DESC,
                                'Beschreibung',
                            ),
                            array(
                                ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DESCTIPTION_SHORT_DESC,
                                'Kurz-Beschreibung',
                            ),
                            array(
                                ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DESCTIPTION_DESC_AND_SHORT_DESC,
                                'Beschreibung + Kurz-Beschreibung',
                            ),
                            array(
                                ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DESCTIPTION_SHORT_DESC_AND_DESC,
                                'Kurz-Beschreibung + Beschreibung',
                            ),
                        )
                    )
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_EXPORT_PRODUCT_DOWNLOADS')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Export von Artikel Downloads')
                    ->setDescription('Legt fest wie Downloads von Artikeln exportiert werden')
                    ->setDefaultValue(ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DOWNLOADS_NO)
                    ->setSelectionValues(
                        array(
                            array(
                                ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DOWNLOADS_NO,
                                'Nicht exportieren',
                            ),
                            array(
                                ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DOWNLOADS_ABOVE_DESCRIPTION,
                                'Über der Beschreibung einfügen',
                            ),
                            array(
                                ShopwareShopgatePluginConfig::EXPORT_PRODUCT_DOWNLOADS_BELOW_DESCRIPTION,
                                'Unter der Beschreibung einfügen',
                            ),
                        )
                    )
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_EXPORT_DIMENSION_UNIT')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Einheit für Produktmaße')
                    ->setDefaultValue('cm')
                    ->setSelectionValues(
                        array(
                            array(-1, '(Maße nicht exportieren)'),
                            array(' ', '(keine Einheit)'),
                            array('cm', 'cm'),
                            array('m', 'm'),
                        )
                    )
                    ->setPosition($position++)
            );

        $attributesStore = $this->getAttributesStore();

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_EXPORT_ATTRIBUTES')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Zu exportierende Freitext Attribute')
                    ->setDescription('Selektieren Sie die Attribute die Sie zusätzlich exportieren wollen')
                    ->setDefaultValue('')
                    ->setHiddenName('id')
                    ->setHiddenValue('id')
                    ->setDisplayField('label')
                    ->setMultiSelect(true)
                    ->setTriggerAction('all')
                    ->setStore($attributesStore)
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_EXPORT_ATTRIBUTES_AS_DESCRIPTION')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Artikel-Eingabefelder an Beschreibung anhängen')
                    ->setDescription(
                        'Selektieren Sie eine Liste der Attribute, ' .
                        'die zusätzlich an die Beschreibung angehangen werden sollen.'
                    )
                    ->setDefaultValue('')
                    ->setHiddenName('id')
                    ->setHiddenValue('id')
                    ->setDisplayField('label')
                    ->setMultiSelect(true)
                    ->setStore($attributesStore)
                    ->setPosition($position++)
            );

        $formElementSelect                 = new FormElementSelect();
        $formElementOptionsContainerSelect = new FormElementOptionsContainerSelect();
        $formElements[]                    = $formElementSelect
            ->setKey('SGATE_SERVER')
            ->setOptions(
                $formElementOptionsContainerSelect
                    ->setLabel('Server')
                    ->setDescription('Dieses Feld nur mit Rücksprache eines Technikers von Shopgate ändern!')
                    ->setDefaultValue('live')
                    ->setSelectionValues(
                        array(
                            array('live', 'Live'),
                            array('pg', 'Playground'),
                            array('custom', 'Development'),
                        )
                    )
                    ->setPosition($position++)
            );

        $formElementText                 = new FormElementText();
        $formElementOptionsContainerText = new FormElementOptionsContainerText;
        $formElements[]                  = $formElementText
            ->setKey('SGATE_SERVER_URL')
            ->setOptions(
                $formElementOptionsContainerText
                    ->setLabel('Server-URL')
                    ->setDescription('Dieses Feld nur mit Rücksprache eines Technikers von Shopgate ändern!')
                    ->setPosition($position++)
            );

        $formElementTextfield                 = new FormElementTextfield();
        $formElementOptionsContainerTextfield = new FormElementOptionsContainerTextfield;
        $formElements[]                       = $formElementTextfield
            ->setKey(ShopwareShopgatePluginConfig::HIDDEN_CONFIG_IDENTIFIER)
            ->setOptions(
                $formElementOptionsContainerTextfield
                    ->setLabel('Shopgate-Config')
                    ->setDescription('Dieses Feld nur mit Rücksprache eines Technikers von Shopgate ändern!')
                    ->setPosition(0)
            )
            ->setVisibility(FormElement::VISIBILITY_HIDDEN);

        return $formElements;
    }

    /**
     * @param FormElement[] $formElements
     */
    protected function detachHiddenConfigFormElements(array $formElements)
    {
        $invisibleElementKeys = array();
        foreach ($formElements as $formElement) {
            if (!$formElement->isVisibile()) {
                $invisibleElementKeys[] = $formElement->getKey();
            }
        }

        $hiddenFormId = ShopwareShopgatePluginConfig::HIDDEN_CONFIGURATION_FORM_ID;
        $sql          = "UPDATE `s_core_config_elements` SET form_id = '{$hiddenFormId}'
				WHERE `name` IN ('" . implode("', '", array_values($invisibleElementKeys)) . "')";

        Shopware()->Db()->query($sql);
    }

    /**
     * Returns all real categories of a specific shop for a given depth level (blog categories are not returned!)
     *
     * @param Category $category
     * @param int      $currentLevel
     * @param int      $maxLevel
     *
     * @return array real categories expect blog categories
     */
    protected function getSubCategories($category, $currentLevel, $maxLevel)
    {
        $categories = array();

        if ($category->getParentId() && !$category->getBlog()) {
            // exclude the real root category of Shopware
            $categories[$category->getId()] = $category;
        }

        if ($currentLevel + 1 <= $maxLevel) {
            foreach ($category->getChildren() as $childCategory) {
                $categories =
                    array_merge($categories, $this->getSubCategories($childCategory, $currentLevel + 1, $maxLevel));
            }
        }

        return $categories;
    }

    ############################################################################
    ## EVENTS
    ############################################################################
    /**
     * Listener for controller -> call framework
     *
     * @param Enlight_Event_EventArgs $args
     *
     * @return string
     * @throws Enlight_Exception
     */
    public static function onGetControllerPathFrontend(
        /** @noinspection PhpUnusedParameterInspection */
        Enlight_Event_EventArgs $args
    ) {
        // Aktueller Shop
        $shop   = Shopware()->Shop()->getId();
        $locale = Shopware()->Locale()->getRegion();

        if (empty($shop) || empty($locale)) {
            throw new Enlight_Exception('Plugin Error - Shop-Id or Locale-Id is empty');
        }

        return dirname(__FILE__) . '/Controllers/Frontend/shopgate.php';
    }

    /**
     * if order delete => delete the shopagte-order which are matched
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onBackendDeleteOrder(Enlight_Event_EventArgs $args)
    {
        $iOrder = $args["subject"]->Request()->getParam("id");

        if (empty($iOrder)) {
            return;
        }

        $bExists = Shopware()->Db()->fetchOne(
            "SELECT shopgate_order_number FROM `s_shopgate_orders` WHERE orderID={$iOrder}"
        );

        if ($bExists) {
            ShopgateLogger::getInstance()->log("Delete Order {$bExists}", ShopgateLogger::LOGTYPE_ACCESS);
            Shopware()->Db()->delete("s_shopgate_orders", "orderID={$iOrder}");
        }
    }

    /**
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onBackendAfterSaveOrder(Enlight_Event_EventArgs $args)
    {
        switch ($args["subject"]->Request()->getParam("action")) {
            case "save":
                $orderId = $args["subject"]->Request()->getParam("id");
                break;
            case "savePosition":
                $orderId = $args["subject"]->Request()->getParam("orderId");
                break;
            case "batchProcess":
                $orders = $args["subject"]->Request()->getParam('orders', array(0 => $args["subject"]->Request()->getParams()));

                if (!empty($orders)) {
                    foreach ($orders as $key => $data) {
                        if (empty($data) || empty($data['id'])) {
                            continue;
                        }

                        $orderId = $data['id'];
                        if ($orderId) {
                            self::cancelOrder($orderId);
                            self::confirmOrderShipping($orderId);
                        }
                    }
                }
                return;
            default:
                return;
        }

        if ($orderId) {
            self::cancelOrder($orderId);
            self::confirmOrderShipping($orderId);
        }
    }

    public static function confirmOrderShipping($orderId)
    {
        /* @var $order \Shopware\CustomModels\Shopgate\Order */

        $order = Shopware()->Models()
            ->getRepository("\\Shopware\\CustomModels\\Shopgate\\Order")
            ->findOneBy(array("orderId" => $orderId));

        if (empty($order)) {
            return;
        }

        $oh = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Order();
        $oh->confirmShipping($order->getOrderNumber());
    }

    public static function cancelOrder($orderId)
    {
        /* @var $order \Shopware\CustomModels\Shopgate\Order */

        $order = Shopware()->Models()
            ->getRepository("\\Shopware\\CustomModels\\Shopgate\\Order")
            ->findOneBy(array("orderId" => $orderId));

        if (empty($order)) {
            return;
        }

        $oh = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Order();
        $oh->cancelOrder($order->getOrderNumber());
    }

    /**
     * Make a Redirect to the Mobile-Page
     *
     * Will called at the Shopware-Event 'Enlight_Controller_Front_SendResponse'
     *
     * @param Enlight_Event_EventArgs $args
     */
    public static function mobileRedirect(Enlight_Event_EventArgs $args)
    {
        $redirector = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Redirect($args);
        $redirector->redirect();

        return;
    }

    /**
     * Return null if order should be send! YES, null!
     *
     * If order mail should not send, return false (!null)
     *
     * @param Enlight_Event_EventArgs $args
     *
     * @return boolean|null
     */
    public static function sendOrderMailForShopgateOrders(Enlight_Event_EventArgs $args)
    {
        $config = new ShopwareShopgatePluginConfig();

        if (defined("_SHOPGATE_API") && _SHOPGATE_API) {
            if (!$config->getSendOrderMail()) {
                return false;
            }
        }

        return null;
    }

    /**
     * Register shop at shopgate
     */
    public function registerSystem()
    {
        $installer = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Install();
        $installer->updateShopgateInstall();
    }

    /**
     * Modifies the checkout view when the user agent is the shopgate app's web view
     *
     * @param Enlight_Event_EventArgs $args
     * @TODO: check compatibility with shopware 4.0.1
     */
    public function onFrontendCheckout(\Enlight_Event_EventArgs $args)
    {

        $view = $args->getSubject()->View();
        if ($args->getRequest()->getActionName() !== 'cart') {
            $view->addTemplateDir(__DIR__ . '/Views/');
            $view->assign('sgWebCheckout', $this->isInWebView());
            $view->assign('sgActionName', $args->getRequest()->getActionName());
            $view->assign('sgSessionId', Shopware()->Session()->offsetGet('sessionId'));
        }

        if ($args->getRequest()->getActionName() === 'finish') {
            $basket = $view->getAssign('sBasket');
            $orderNumber = $view->getAssign('sOrderNumber');

            $params = [
                'number' => $orderNumber,
                'currency' => $basket['sCurrencyName'],
                'totals' => [
                    [
                        'type' => 'shipping',
                        'amount' => $basket['sShippingcosts'],
                    ],
                    [
                        'type' => 'tax',
                        'amount' => $basket['sAmountTax']
                    ],
                    [
                        'type' => 'grandTotal',
                        'amount' => $basket['AmountNumeric']
                    ]
                ],
                'products' => []
            ];

            foreach ($basket['content'] as $item) {
                $params['products'][] = [
                    'id' => $item['id'],
                    'name' => $item['articlename'],
                    'quantity' => $item['quantity'],
                    'price' => [
                        'withTax' => floatval($item['priceNumeric']),
                        'net' => floatval($item['netprice'])
                    ]
                ];
            }

            $view->assign('sgCheckoutParams', json_encode($params));
        }
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onFrontendRegister(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->addTemplateDir(__DIR__ . '/Views/');
        $view->assign('sgWebCheckout', $this->isInWebView());
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onFrontendAccount(\Enlight_Event_EventArgs $args)
    {
        $sgCloudCallbackData = Shopware()->Session()->offsetGet('sgCloudCallbackData');

        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        $view->assign('sgWebCheckout', $this->isInWebView());
        $view->assign('sgForgotPassword', false);

        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $user = $user['additional']['user'];
        $hash = $user['password'];
        $email = $user['email'];

        $view->assign('sgCloudCallbackData', $sgCloudCallbackData);
        $view->assign('sgHash', $hash);
        $view->assign('sgEmail', $email);
    }

    public function onFrontendPassword(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->assign('sgForgotPassword', true);
    }

    public function onFrontendCustom(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        $view->assign('sgWebCheckout', $this->isInWebView());
    }

    /**
     * Removes hidden config form element
     */
    protected function deleteHiddenConfig()
    {
        // select form element id (there is only one "hidden" config form element
        $sql    = "SELECT id FROM `s_core_config_elements` "
            . "WHERE `name` LIKE '" . ShopwareShopgatePluginConfig::HIDDEN_CONFIG_IDENTIFIER . "'";
        $result = Shopware()->Db()->fetchCol($sql);
        if ($result && count($result) > 0) {
            $id = $result[0];

            $sql = "
				DELETE FROM `s_core_config_values` WHERE `element_id` = ?;
				DELETE FROM `s_core_config_element_translations` WHERE `element_id` = ?;
				DELETE FROM `s_core_config_elements` WHERE `id` = ?;
			";
            Shopware()->Db()->query(
                $sql,
                array(
                    $id, // element_id in s_core_config_values
                    $id, // element_id in s_core_config_element_translations
                    $id, // id in s_core_config_elements
                )
            );
        }
    }

    /**
     * This is code from the Shopware support team.
     *
     * @param bool $throwException
     *
     * @return bool|string
     * @throws Exception
     */
    public function checkLicense($throwException = true)
    {
        try {
            /** @var $license Shopware_Components_License */
            $license = Shopware()->License();
        } catch (\Exception $e) {
            if ($throwException) {
                throw new Exception('The license manager has to be installed and active');
            } else {
                return false;
            }
        }

        try {
            static $hashOfAnEmptyString, $module = 'SgateShopgatePlugin';
            if (!isset($hashOfAnEmptyString)) {
                $user                       = base64_decode('wHkEvk0rRvRfykxftL/6V/rjakE=');
                $pass                       = base64_decode('TaEQIempiaLPUAZbJ3+pZFMngWM=');
                $hashOfAnEmptyString        = sha1(uniqid('', true), true);
                $notCoreLicense             = $license->getLicense($module, $hashOfAnEmptyString);
                $coreLicense                = $license->getCoreLicense();
                $isTheLengthOfTheStringOkay = strlen($coreLicense) === 20
                    ? sha1($coreLicense . $user . $coreLicense, true)
                    : 0;
                $isTheLicenseCorrect        = $notCoreLicense === sha1(
                        $pass . $isTheLengthOfTheStringOkay . $hashOfAnEmptyString,
                        true
                    );
            }
            if (!$isTheLicenseCorrect && $throwException) {
                throw new Exception('License check for module "' . $module . '" has failed.');
            }

            return $isTheLicenseCorrect;
        } catch (Exception $e) {
            if ($throwException) {
                throw new Exception('License check for module "' . $module . '" has failed.');
            } else {
                return false;
            }
        }
    }

    /**
     * @param Status $shopwareOrderStatus
     *
     * @return string
     */
    private function getOrderStatusName(Status $shopwareOrderStatus)
    {
        if ($this->orderStatusUseGetDescription === null) {
            $this->orderStatusUseGetDescription = method_exists($shopwareOrderStatus, 'getDescription');
        }

        // method "getDescription" deprecated, introduced compatibility fallback and ignoring inspection (only here)

        /** @noinspection PhpDeprecationInspection */
        return $this->orderStatusUseGetDescription
            ? $shopwareOrderStatus->getDescription()
            : $shopwareOrderStatus->getName();
    }

    /**
     * The ajax call to retrieve all attributes does not work anymore since Shopware version 5.2
     * For now we are getting the attributes in the install process. See SHOPWARE-732
     *
     * @return string
     */
    private function getAttributesStore()
    {
        if (version_compare(Shopware()->Config()->version, '5.2', '>=')) {
            $attributeHelper = new AttributeHelper();
            $attributesStore = $attributeHelper->getConfiguredAttributes(false);
        } else {
            $remoteUrl       = Shopware()->Front()->Router()->assemble(
                array(
                    "controller"       => "Config",
                    "action"           => "getList",
                    "_repositoryClass" => "attribute",
                    "name"             => "attribute",
                )
            );
            $attributesStore = FormElementOptionsContainerSelect::buildStoreAjax(
                array('id', 'label'),
                $remoteUrl
            );
        }

        return $attributesStore;
    }

    /**
     * Gets order number
     *
     * @return string
     */
    protected function getOrderNumber()
    {
        return Shopware()->Session()->sOrderVariables->sOrderNumber;
    }

    /**
     * @return bool
     */
    protected function isInWebView()
    {
        $shopgateApp = Shopware()->Session()->offsetGet('sgWebView');

        if (isset($shopgateApp) && $shopgateApp) {
            return true;
        }

        return false;
    }
}
