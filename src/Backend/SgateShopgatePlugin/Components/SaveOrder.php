<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
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
 *
 * @category   Shopware
 * @package    Shopware_Core
 * @subpackage Class
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Stefan Hamann
 * @author     $Author$
 */
/**
 * Deprecated Shopware Class that handle frontend orders
 *
 * todo@all: Documentation
 */

/**
 * This is a copy of the core-Order class for saving orders into shopware. Contains little modifications by Shopgate
 * GmbH
 */
class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_SaveOrder
{
    /**
     * Array with userdata
     *
     * @var array
     */
    public $sUserData;

    /**
     * Array with basketdata
     *
     * @var array
     */
    public $sBasketData;

    /**
     * Array with shipping / dispatch data
     *
     * @var array
     */
    public $sShippingData;

    /**
     * User comment to save within this order
     *
     * @var string
     */
    public $sComment;

    /**
     * Payment-mean object
     *
     * @var object
     */
    public $paymentObject;

    /**
     * Total amount net
     *
     * @var double
     */
    public $sAmountNet;

    /**
     * Total Amount
     *
     * @var double
     */
    public $sAmount;

    /**
     * Total Amount with tax (force)
     *
     * @var double
     */
    public $sAmountWithTax;

    /**
     * Shipppingcosts
     *
     * @var double
     */
    public $sShippingcosts;

    /**
     * Shippingcosts unformated
     *
     * @var double
     */
    public $sShippingcostsNumeric;

    /**
     * Shippingcosts net unformated
     *
     * @var double
     */
    public $sShippingcostsNumericNet;

    /**
     * Pointer to sSystem object
     *
     * @var object
     */
    public $sSYSTEM;

    /**
     * TransactionID (epayment)
     *
     * @var string
     */
    public $bookingId;

    /**
     * Ordernumber
     *
     * @var string
     */
    public $sOrderNumber;

    /**
     * ID of choosen dispatch
     *
     * @var int
     */
    public $dispatchId;

    /**
     * Random id to identify the order
     *
     * @var string
     */
    public $uniqueID;

    /**
     * Net order true /false
     *
     * @var bool
     */
    public $sNet; // Complete taxfree

    /**
     * Custom attributes
     *
     * @var string
     */
    public $o_attr_1;
    public $o_attr_2;
    public $o_attr_3;
    public $o_attr_4;
    public $o_attr_5;
    public $o_attr_6;

    /**
     * Shopgate Config
     *
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config
     */
    public $sgConfig;

    /**
     * @var string[]
     */
    private $billingAddressFields;

    /**
     * @var string[]
     */
    private $shippingAddressFields;

    /**
     * Database connection which used for each database operation in this class.
     * Injected over the class constructor
     *
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * Shopware session namespace object which is used
     * for each session access in this class.
     * Injected over the class constructor
     *
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * copy-constructor to copy all object vars to the actual object
     *
     * @param sOrder                                                         $shopwareOrderObj
     * @param Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config $shopgateConfig
     */
    public function __construct(
        sOrder $shopwareOrderObj,
        Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config $shopgateConfig
    ) {
        foreach (get_object_vars($shopwareOrderObj) as $key => $value) {
            $this->$key = $value;
        }
        $this->sgConfig             = $shopgateConfig;
        $this->db                   = Shopware()->Db();
        $this->billingAddressFields = array(
            'userID',
            'orderID',
            'company',
            'department',
            'salutation',
            'customernumber',
            'firstname',
            'lastname',
            'street',
            'zipcode',
            'city',
            'phone',
            'countryID',
            'stateID',
            'ustid',
        );

        $this->shippingAddressFields = array(
            'userID',
            'orderID',
            'company',
            'department',
            'salutation',
            'firstname',
            'lastname',
            'street',
            'zipcode',
            'city',
            'countryID',
            'stateID',
        );

        if (!$this->sgConfig->assertMinimumVersion('5.0.0')) {
            array_splice(
                $this->billingAddressFields,
                array_search('street', $this->billingAddressFields) + 1,
                0,
                'streetnumber'
            );
            array_splice(
                $this->shippingAddressFields,
                array_search('street', $this->shippingAddressFields) + 1,
                0,
                'streetnumber'
            );
        }

        if (!$this->sgConfig->assertMinimumVersion('5.2.0')) {
            array_splice($this->billingAddressFields, array_search('phone', $this->billingAddressFields) + 1, 0, 'fax');
        }
    }

    /**
     * @param        $msg
     * @param string $type
     *
     * @return bool
     */
    public function log($msg, $type = ShopgateLogger::LOGTYPE_ERROR)
    {
        return ShopgateLogger::getInstance()->log($msg, $type);
    }

    /**
     * Get a unique ordernumber
     *
     * @access public
     * @return string ordernumber
     */
    public function sGetOrderNumber()
    {
        return Shopware()->Modules()->Order()->sGetOrderNumber();
    }

    /**
     * Check each basketrow for instant downloads
     *
     * @access public
     */
    public function sManageEsdOrder(&$basketRow, $orderID, $orderdetailsID)
    {
        $quantity                     = $basketRow["quantity"];
        $basketRow['assignedSerials'] = array();

        //check if current order number is an esd variant.
        $esdArticle = $this->getVariantEsd($basketRow["ordernumber"]);

        if (!$esdArticle["id"]) {
            // ESD not found
            return;
        }

        if (!$esdArticle["serials"]) {
            // No serial number is needed
            $this->db->insert(
                's_order_esd',
                array(
                    'serialID'       => 0,
                    'esdID'          => $esdArticle["id"],
                    'userID'         => $this->sUserData["additional"]["user"]["id"],
                    'orderID'        => $orderID,
                    'orderdetailsID' => $orderdetailsID,
                    'datum'          => new Zend_Db_Expr('NOW()'),
                )
            );

            return;
        }

        $availableSerials = $this->getAvailableSerialsOfEsd($esdArticle["id"]);

        if ((count($availableSerials) <= $this->sSYSTEM->sCONFIG['esdMinSerials'])
            || count($availableSerials) <= $quantity
        ) {
            // No serialnumber anymore, inform merchant
            $context = array(
                'sArticleName' => $basketRow["articlename"],
                'sMail'        => $this->sUserData["additional"]["user"]["email"],
            );

            $mail = Shopware()->TemplateMail()->createMail('sNOSERIALS', $context);

            if ($this->sSYSTEM->sCONFIG['sESDMAIL']) {
                $mail->addTo($this->sSYSTEM->sCONFIG['sESDMAIL']);
            } else {
                $mail->addTo($this->sSYSTEM->sCONFIG['sMAIL']);
            }

            $mail->send();
        }

        // Check if enough serials are available, if not, an email has been sent, and we can return
        if (count($availableSerials) < $quantity) {
            return;
        }

        for ($i = 1; $i <= $quantity; $i++) {
            // Assign serialnumber
            $serialId = $availableSerials[$i - 1]["id"];

            // Update basketrow
            $basketRow['assignedSerials'][] = $availableSerials[$i - 1]["serialnumber"];

            $this->db->insert(
                's_order_esd',
                array(
                    'serialID'       => $serialId,
                    'esdID'          => $esdArticle["id"],
                    'userID'         => $this->sUserData["additional"]["user"]["id"],
                    'orderID'        => $orderID,
                    'orderdetailsID' => $orderdetailsID,
                    'datum'          => new Zend_Db_Expr('NOW()'),
                )
            );
        }
    }

    /**
     * Helper function which returns all available esd serials for the passed esd id.
     *
     * @param $esdId
     *
     * @return array
     */
    private function getAvailableSerialsOfEsd($esdId)
    {
        return $this->db->fetchAll(
            "SELECT s_articles_esd_serials.id AS id, s_articles_esd_serials.serialnumber as serialnumber
                        FROM s_articles_esd_serials
                        LEFT JOIN s_order_esd
                          ON (s_articles_esd_serials.id = s_order_esd.serialID)
                        WHERE s_order_esd.serialID IS NULL
                        AND s_articles_esd_serials.esdID= :esdId",
            array('esdId' => $esdId)
        );
    }

    /**
     * Helper function which returns the esd definition of the passed variant
     * order number.
     * Used for the sManageEsd function to check if the current order article variant
     * is an esd variant.
     *
     * @param $orderNumber
     *
     * @return array|false
     */
    private function getVariantEsd($orderNumber)
    {
        return $this->db->fetchRow(
            "SELECT s_articles_esd.id AS id, serials
                        FROM  s_articles_esd, s_articles_details
                        WHERE s_articles_esd.articleID = s_articles_details.articleID
                        AND   articledetailsID = s_articles_details.id
                        AND   s_articles_details.ordernumber= :orderNumber",
            array(':orderNumber' => $orderNumber)
        );
    }

    /**
     * Delete temporary created order
     *
     * @access public
     */
    public function sDeleteTemporaryOrder()
    {
        if (empty($this->sSYSTEM->sSESSION_ID)) {
            return;
        }
        $deleteWholeOrder = $this->db->fetchAll(
            "SELECT * FROM s_order
                                        WHERE temporaryID = ? LIMIT 2 ",
            array($this->getSession()->offsetGet('sessionId'))
        );

        foreach ($deleteWholeOrder as $orderDelete) {
            $this->db->query(
                "DELETE FROM s_order WHERE id = ?",
                array($orderDelete["id"])
            );

            $this->db->query(
                "DELETE FROM s_order_details
                         WHERE orderID=?",
                array($orderDelete["id"])
            );
        }
    }

    /**
     * Create temporary order (for order cancelation reports)
     *
     * @access public
     */
    public function sCreateTemporaryOrder()
    {
        $this->sShippingData["AmountNumeric"] =
            $this->sShippingData["AmountNumeric"]
                ? $this->sShippingData["AmountNumeric"]
                : "0";
        if (!$this->sShippingcostsNumeric) {
            $this->sShippingcostsNumeric = "0";
        }
        if (!$this->sBasketData["AmountWithTaxNumeric"]) {
            $this->sBasketData["AmountWithTaxNumeric"] = $this->sBasketData["AmountNumeric"];
        }

        // Check if tax-free
        if (($this->sSYSTEM->sCONFIG['sARTICLESOUTPUTNETTO'] && !$this->sSYSTEM->sUSERGROUPDATA["tax"])
            || (!$this->sSYSTEM->sUSERGROUPDATA["tax"] && $this->sSYSTEM->sUSERGROUPDATA["id"])
        ) {
            $net = "1";
        } else {
            $net = "0";
        }

        $this->sBasketData["AmountNetNumeric"] = round($this->sBasketData["AmountNetNumeric"], 2);
        if ($this->dispatchId) {
            $dispatchId = $this->dispatchId;
        } else {
            $dispatchId = "0";
        }

        $this->sBasketData["AmountNetNumeric"] = round($this->sBasketData["AmountNetNumeric"], 2);

        if (empty($this->sSYSTEM->sCurrency["currency"])) {
            $this->sSYSTEM->sCurrency["currency"] = "EUR";
        }
        if (empty($this->sSYSTEM->sCurrency["factor"])) {
            $this->sSYSTEM->sCurrency["factor"] = "1";
        }

        $shop     = Shopware()->Shop();
        $mainShop = $shop->getMain() !== null
            ? $shop->getMain()
            : $shop;

        $taxfree = "0";
        if (!empty($this->sNet)) {
            // Complete net delivery
            $net                                       = "1";
            $this->sBasketData["AmountWithTaxNumeric"] = $this->sBasketData["AmountNetNumeric"];
            $this->sShippingcostsNumeric               = $this->sShippingcostsNumericNet;
            $taxfree                                   = "1";
        }
        if (empty($this->sBasketData["AmountWithTaxNumeric"])) {
            $this->sBasketData["AmountWithTaxNumeric"] = '0';
        }
        if (empty($this->sBasketData["AmountNetNumeric"])) {
            $this->sBasketData["AmountNetNumeric"] = '0';
        }

        $data = array(
            'ordernumber'          => '0',
            'userID'               => $this->sUserData["additional"]["user"]["id"],
            'invoice_amount'       => $this->sBasketData["AmountWithTaxNumeric"],
            'invoice_amount_net'   => $this->sBasketData["AmountNetNumeric"],
            'invoice_shipping'     => $this->sShippingcostsNumeric,
            'invoice_shipping_net' => $this->sShippingcostsNumericNet,
            'ordertime'            => new Zend_Db_Expr('NOW()'),
            'status'               => -1,
            'paymentID'            => $this->sUserData["additional"]["user"]["paymentID"],
            'customercomment'      => $this->sComment,
            'net'                  => $net,
            'taxfree'              => $taxfree,
            'partnerID'            => (string)$this->getSession()->offsetGet("sPartner"),
            'temporaryID'          => $this->getSession()->offsetGet('sessionId'),
            'referer'              => (string)$this->getSession()->offsetGet('sReferer'),
            'language'             => $shop->getId(),
            'dispatchID'           => $dispatchId,
            'currency'             => $this->sSYSTEM->sCurrency["currency"],
            'currencyFactor'       => $this->sSYSTEM->sCurrency["factor"],
            'subshopID'            => $mainShop->getId(),
            'deviceType'           => $this->deviceType,
        );

        try {
            $affectedRows = $this->db->insert('s_order', $data);
            $orderID      = $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Enlight_Exception("##sOrder-sTemporaryOrder-#01:" . $e->getMessage(), 0, $e);
        }
        if (!$affectedRows || !$orderID) {
            throw new Enlight_Exception("##sOrder-sTemporaryOrder-#01: No rows affected or no order id saved", 0);
        }

        $position = 0;
        foreach ($this->sBasketData["content"] as $basketRow) {
            $position++;

            if (!$basketRow["price"]) {
                $basketRow["price"] = "0,00";
            }

            $basketRow["articlename"] = html_entity_decode($basketRow["articlename"]);
            $basketRow["articlename"] = strip_tags($basketRow["articlename"]);

            $basketRow["articlename"] = $this->sSYSTEM->sMODULES['sArticles']->sOptimizeText($basketRow["articlename"]);

            if (!$basketRow["esdarticle"]) {
                $basketRow["esdarticle"] = "0";
            }
            if (!$basketRow["modus"]) {
                $basketRow["modus"] = "0";
            }
            if (!$basketRow["taxID"]) {
                $basketRow["taxID"] = "0";
            }
            if (!$basketRow["releasedate"]) {
                $basketRow["releasedate"] = '0000-00-00';
            }

            $data = array(
                'orderID'            => $orderID,
                'ordernumber'        => 0,
                'articleID'          => $basketRow["articleID"],
                'articleordernumber' => $basketRow["ordernumber"],
                'price'              => $basketRow["priceNumeric"],
                'quantity'           => $basketRow["quantity"],
                'name'               => $basketRow["articlename"],
                'status'             => 0,
                'releasedate'        => $basketRow["releasedate"],
                'modus'              => $basketRow["modus"],
                'esdarticle'         => $basketRow["esdarticle"],
                'taxID'              => $basketRow["taxID"],
                'tax_rate'           => $basketRow["tax_rate"],
            );

            try {
                $this->db->insert('s_order_details', $data);
            } catch (Exception $e) {
                throw new Enlight_Exception("##sOrder-sTemporaryOrder-Position-#02:" . $e->getMessage(), 0, $e);
            }
        } // For every article in basket

        return;
    }

    /**
     * Checks if the passed transaction id is already set as transaction id of an
     * existing order.
     *
     * @param $transactionId
     *
     * @return bool
     */
    private function isTransactionExist($transactionId)
    {
        if (strlen($transactionId) <= 3) {
            return false;
        }

        $insertOrder = $this->db->fetchRow(
            "SELECT id FROM s_order WHERE transactionID = ? AND status != -1",
            array($transactionId)
        );

        return !empty($insertOrder["id"]);
    }

    /**
     * Finaly save order and send order confirmation to customer
     *
     * @access public
     */
    public function sSaveOrder()
    {
        $this->log(
            "> Shopware_Plugins_Backend_SgateShopgatePlugin_Components_SaveOrder::sSaveOrder #BEGIN",
            ShopgateLogger::LOGTYPE_DEBUG
        );

        $this->sComment = stripslashes($this->sComment);
        $this->sComment = stripcslashes($this->sComment);

        if ($this->isTransactionExist($this->bookingId)) {
            return false;
        }

        // Insert basic-data of the order
        $orderNumber        = $this->sGetOrderNumber();
        $this->sOrderNumber = $orderNumber;

        $esdOrder = false;

        if (!$this->sShippingcostsNumeric) {
            $this->sShippingcostsNumeric = "0";
        }

        if (!$this->sBasketData["AmountWithTaxNumeric"]) {
            $this->sBasketData["AmountWithTaxNumeric"] = $this->sBasketData["AmountNumeric"];
        }

        // Check if tax-free
        if (($this->sSYSTEM->sCONFIG['sARTICLESOUTPUTNETTO'] && !$this->sSYSTEM->sUSERGROUPDATA["tax"])
            || (!$this->sSYSTEM->sUSERGROUPDATA["tax"] && $this->sSYSTEM->sUSERGROUPDATA["id"])
        ) {
            $net = "1";
        } else {
            $net = "0";
        }

        if ($this->dispatchId) {
            $dispatchId = $this->dispatchId;
        } else {
            $dispatchId = "0";
        }

        $this->sBasketData["AmountNetNumeric"] = round($this->sBasketData["AmountNetNumeric"], 2);

        if (empty($this->sSYSTEM->sCurrency["currency"])) {
            $this->sSYSTEM->sCurrency["currency"] = "EUR";
        }
        if (empty($this->sSYSTEM->sCurrency["factor"])) {
            $this->sSYSTEM->sCurrency["factor"] = "1";
        }

        $shop     = Shopware()->Shop();
        $mainShop = $shop->getMain() !== null
            ? $shop->getMain()
            : $shop;

        $taxfree = "0";
        if (!empty($this->sNet)) {
            // Complete net delivery
            $net                                       = "1";
            $this->sBasketData["AmountWithTaxNumeric"] = $this->sBasketData["AmountNetNumeric"];
            $this->sShippingcostsNumeric               = $this->sShippingcostsNumericNet;
            $taxfree                                   = "1";
        }

        $partner = $this->getPartnerCode(
            $this->sUserData["additional"]["user"]["affiliate"]
        );

        $orderParams = array(
            'ordernumber'          => $orderNumber,
            'userID'               => $this->sUserData["additional"]["user"]["id"],
            'invoice_amount'       => $this->sBasketData["AmountWithTaxNumeric"],
            'invoice_amount_net'   => $this->sBasketData["AmountNetNumeric"],
            'invoice_shipping'     => floatval($this->sShippingcostsNumeric),
            'invoice_shipping_net' => floatval($this->sShippingcostsNumericNet),
            'ordertime'            => new Zend_Db_Expr('NOW()'),
            'status'               => 0,
            'cleared'              => 17,
            'paymentID'            => $this->sUserData["additional"]["user"]["paymentID"],
            'transactionID'        => (string)$this->bookingId,
            'customercomment'      => $this->sComment,
            'net'                  => $net,
            'taxfree'              => $taxfree,
            'partnerID'            => (string)$partner,
            'temporaryID'          => (string)$this->uniqueID,
            'referer'              => (string)$this->getSession()->offsetGet('sReferer'),
            'language'             => $shop->getId(),
            'dispatchID'           => $dispatchId,
            'currency'             => $this->sSYSTEM->sCurrency["currency"],
            'currencyFactor'       => $this->sSYSTEM->sCurrency["factor"],
            'subshopID'            => $mainShop->getId(),
            'remote_addr'          => (string)$_SERVER['REMOTE_ADDR'],
        );

        $orderParams = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveOrder_FilterParams',
            $orderParams,
            array('subject' => $this)
        );

        try {
            $this->db->beginTransaction();
            $affectedRows = $this->db->insert('s_order', $orderParams);
            $orderID      = $this->db->lastInsertId();
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Enlight_Exception(
                "Shopware Order Fatal-Error {$_SERVER["HTTP_HOST"]} :" . $e->getMessage(), 0, $e
            );
        }

        if (!$affectedRows || !$orderID) {
            throw new Enlight_Exception(
                "Shopware Order Fatal-Error {$_SERVER["HTTP_HOST"]} : No rows affected or no order id created.", 0
            );
        }

        //new attribute table with shopware 4
        $attributeSql = "INSERT INTO s_order_attributes (orderID, attribute1, attribute2, attribute3, attribute4, attribute5, attribute6)
                VALUES (
                    " . $orderID . ",
                    " . $this->db->quote((string)$this->o_attr_1) . ",
                    " . $this->db->quote((string)$this->o_attr_2) . ",
                    " . $this->db->quote((string)$this->o_attr_3) . ",
                    " . $this->db->quote((string)$this->o_attr_4) . ",
                    " . $this->db->quote((string)$this->o_attr_5) . ",
                    " . $this->db->quote((string)$this->o_attr_6) . "
                )";
        $attributeSql = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveOrderAttributes_FilterSQL',
            $attributeSql,
            array('subject' => $this)
        );
        $this->db->exec($attributeSql);

        // add attributes to order
        $sql        = 'SELECT * FROM s_order_attributes WHERE orderID = :orderId;';
        $attributes = Shopware()->Db()->fetchRow($sql, array('orderId' => $orderID));
        unset($attributes['id']);
        unset($attributes['orderID']);
        $orderAttributes = $attributes;

        $orderDay  = date("d.m.Y");
        $orderTime = date("H:i");

        $position = 0;

        $ean      = "";
        $eanValue = "";
        if ($this->orderDetailsHaveEan()) {
            $ean = ", ean";
        }

        foreach ($this->sBasketData["content"] as $key => $basketRow) {
            $position++;

            $amountRow = $this->sSYSTEM->sMODULES['sArticles']->sFormatPrice(
                $basketRow["priceNumeric"] * $basketRow["quantity"]
            );

            if (!$basketRow["price"]) {
                $basketRow["price"] = "0,00";
            }
            if (!$amountRow) {
                $amountRow = "0,00";
            }

            $basketRow["articlename"] = str_replace("<br />", "\n", $basketRow["articlename"]);
            $basketRow["articlename"] = html_entity_decode($basketRow["articlename"]);
            $basketRow["articlename"] = strip_tags($basketRow["articlename"]);

            if (empty($basketRow["itemInfo"])) {
                $priceRow = $basketRow["price"];
            } else {
                $priceRow = $basketRow["itemInfo"];
            }

            $basketRow["articlename"] = $this->sSYSTEM->sMODULES['sArticles']->sOptimizeText($basketRow["articlename"]);

            if (empty($basketRow["esdarticle"])) {
                $basketRow["esdarticle"] = "0";
            }
            if (empty($basketRow["modus"])) {
                $basketRow["modus"] = "0";
            }
            if (empty($basketRow["taxID"])) {
                $basketRow["taxID"] = "0";
            }
            if (empty($basketRow["tax_rate"])) {
                $basketRow["tax_rate"] = "0";
            }
            if ($this->sNet == true) {
                $basketRow["taxID"] = "0";
            }
            if (!empty($ean)) {
                $eanValue = ",'{$basketRow["ean"]}'";
            }

            $sql = "
			INSERT INTO s_order_details
				(orderID,
				ordernumber,
				articleID,
				articleordernumber,
				price,
				quantity,
				name,
				status,
				releasedate,
				modus,
				esdarticle,
				taxID,
				tax_rate
				$ean
				)
				VALUES (
				$orderID,
				'$orderNumber',
				{$basketRow["articleID"]},
				'{$basketRow["ordernumber"]}',
				{$basketRow["priceNumeric"]},
				{$basketRow["quantity"]},
				'" . addslashes($basketRow["articlename"]) . "',
				0,
				'0000-00-00',
				{$basketRow["modus"]},
				{$basketRow["esdarticle"]},
				{$basketRow["taxID"]},
				{$basketRow["tax_rate"]}
				$eanValue
			)";
            $sql = Enlight()->Events()->filter(
                'Shopware_Modules_Order_SaveOrder_FilterDetailsSQL',
                $sql,
                array(
                    'subject' => $this,
                    'row'     => $basketRow,
                    'user'    => $this->sUserData,
                    'order'   => array(
                        "id"     => $orderID,
                        "number" => $orderNumber,
                    ),
                )
            );

            // Check for individual voucher - code
            if ($basketRow["modus"] == 2) {
                $getVoucher = $this->db->fetchRow(
                    "SELECT modus,id FROM s_emarketing_vouchers WHERE ordercode=?",
                    array($basketRow["ordernumber"])
                );

                if ($getVoucher["modus"] == 1) {
                    // Update Voucher - Code
                    $updateVoucher = $this->db->exec(
                        "UPDATE s_emarketing_voucher_codes SET cashed = 1, userID= " . $this->sUserData["additional"]["user"]["id"] .
                        " WHERE id = " . $basketRow["articleID"]
                    );
                }
            }

            if ($basketRow["esdarticle"]) {
                $esdOrder = true;
            }

            try {
                $this->db->exec($sql);
                $orderdetailsID = $this->db->lastInsertId();
            } catch (Exception $e) {
                $this->log('Error in execution of: ' . $sql);
                throw new Enlight_Exception(
                    "Shopware Order Fatal-Error {$_SERVER["HTTP_HOST"]} :" . $e->getMessage(),
                    0,
                    $e
                );
            }

            $this->sBasketData['content'][$key]['orderDetailId'] = $orderdetailsID;

            for ($obAttrIdx = 1; $obAttrIdx <= 6; $obAttrIdx++) {
                if (empty($basketRow["ob_attr{$obAttrIdx}"])) {
                    $basketRow["ob_attr{$obAttrIdx}"] = null;
                }
            }

            //new attribute tables
            $attributeSql = "INSERT INTO s_order_details_attributes (detailID, attribute1, attribute2, attribute3, attribute4, attribute5, attribute6)
                             VALUES ("
                . $orderdetailsID . "," .
                $this->db->quote((string)$basketRow["ob_attr1"]) . "," .
                $this->db->quote((string)$basketRow["ob_attr2"]) . "," .
                $this->db->quote((string)$basketRow["ob_attr3"]) . "," .
                $this->db->quote((string)$basketRow["ob_attr4"]) . "," .
                $this->db->quote((string)$basketRow["ob_attr5"]) . "," .
                $this->db->quote((string)$basketRow["ob_attr6"]) .
                ")";
            $attributeSql = Enlight()->Events()->filter(
                'Shopware_Modules_Order_SaveOrderAttributes_FilterDetailsSQL',
                $attributeSql,
                array(
                    'subject' => $this,
                    'row'     => $basketRow,
                    'user'    => $this->sUserData,
                    'order'   => array(
                        "id"     => $orderID,
                        "number" => $orderNumber,
                    ),
                )
            );
            $this->db->exec($attributeSql);

            // add attributes
            $sql        = 'SELECT * FROM s_order_details_attributes WHERE detailID = :detailID;';
            $attributes = Shopware()->Db()->fetchRow($sql, array('detailID' => $orderdetailsID));
            unset($attributes['id']);
            unset($attributes['detailID']);
            $orderDetail['attributes']                        = $attributes;
            $this->sBasketData['content'][$key]['attributes'] = $attributes;

            // Update sales and stock
            if ($basketRow["priceNumeric"] >= 0) {
                $this->db->exec(
                    "UPDATE s_articles_details
				  SET sales=sales+{$basketRow["quantity"]},instock=instock-{$basketRow["quantity"]}
				  WHERE ordernumber='{$basketRow["ordernumber"]}'"
                );
            }

            if (!empty($basketRow["laststock"]) && !empty($this->sSYSTEM->sCONFIG['sDEACTIVATENOINSTOCK'])
                && !empty($basketRow['articleID'])
            ) {
                $sql         = 'SELECT MAX(instock) as max_instock FROM s_articles_details WHERE articleID=?';
                $max_instock = Shopware()->Db()->fetchOne($sql, array($basketRow['articleID']));
                $max_instock = (int)$max_instock;
                if ($max_instock <= 0) {
                    $sql = 'UPDATE s_articles SET active=0 WHERE id=?';
                    $this->db->query($sql, array($basketRow['articleID']));
                    // Ticket #5517
                    $this->db->query(
                        "UPDATE s_articles_details SET active = 0 WHERE ordernumber = ?",
                        array($basketRow['ordernumber'])
                    );
                }
            }

            // For esd-articles, assign serialnumber if needed
            // Check if this article is esd-only (check in variants, too -> later)
            if ($basketRow["esdarticle"]) {
                $this->sManageEsdOrder($basketRow, $orderID, $orderdetailsID);

                // Add assignedSerials to basketcontent
                if (!empty($basketRow['assignedSerials'])) {
                    $this->sBasketData["content"][$key]['serials'] = $basketRow['assignedSerials'];
                }
            }
        } // For every artice in basket

        // 		Enlight()->Events()->notify('Shopware_Modules_Order_SaveOrder_ProcessDetails', array(
        //             'subject' => $this,
        //             'details' => $this->sBasketData['content'],
        //         ));

        // Assign variables
        foreach ($this->sUserData["billingaddress"] as $key => $value) {
            $this->sUserData["billingaddress"][$key] = html_entity_decode($value);
        }
        foreach ($this->sUserData["shippingaddress"] as $key => $value) {
            $this->sUserData["shippingaddress"][$key] = html_entity_decode($value);
        }
        if (!empty($this->sUserData["additional"]["country"])) {
            foreach ($this->sUserData["additional"]["country"] as $key => $value) {
                $this->sUserData["additional"]["country"][$key] = html_entity_decode($value);
            }
        }

        $this->sUserData["additional"]["payment"]["description"] =
            html_entity_decode($this->sUserData["additional"]["payment"]["description"]);

        $sOrderDetails = array();
        foreach ($this->sBasketData["content"] as $content) {
            $content["articlename"] = trim(html_entity_decode($content["articlename"]));
            $content["articlename"] = str_replace(array("<br />", "<br>"), "\n", $content["articlename"]);
            $content["articlename"] = str_replace("&euro;", "€", $content["articlename"]);
            $content["articlename"] = trim($content["articlename"]);

            while (strpos($content["articlename"], "\n\n") !== false) {
                $content["articlename"] = str_replace("\n\n", "\n", $content["articlename"]);
            }

            $content["ordernumber"] = trim(html_entity_decode($content["ordernumber"]));

            $content['amount']    = $this->sSYSTEM->sMODULES['sArticles']->sFormatPrice(
                (int)$content['quantity'] * $content['amount']
            );
            $content['amountnet'] = $this->sSYSTEM->sMODULES['sArticles']->sFormatPrice(
                (int)$content['quantity']
                * $content['amountnet']
            );

            $sOrderDetails[] = $content;
        }

        $variables = array(
            "sOrderDetails"   => $sOrderDetails,
            "billingaddress"  => $this->sUserData["billingaddress"],
            "shippingaddress" => $this->sUserData["shippingaddress"],
            "additional"      => $this->sUserData["additional"],
            "sShippingCosts"  => $this->sSYSTEM->sMODULES['sArticles']->sFormatPrice($this->sShippingcosts) . " "
                . $this->sSYSTEM->sCurrency["currency"],
            "sAmount"         => $this->sAmountWithTax
                ? $this->sSYSTEM->sMODULES['sArticles']->sFormatPrice($this->sAmountWithTax) . " "
                . $this->sSYSTEM->sCurrency["currency"]
                : $this->sSYSTEM->sMODULES['sArticles']->sFormatPrice($this->sAmount) . " "
                . $this->sSYSTEM->sCurrency["currency"],
            "sAmountNet"      =>
                $this->sSYSTEM->sMODULES['sArticles']->sFormatPrice($this->sBasketData["AmountNetNumeric"]) . " "
                . $this->sSYSTEM->sCurrency["currency"],
            "ordernumber"     => $orderNumber,
            "sOrderDay"       => $orderDay,
            "sOrderTime"      => $orderTime,
            "sComment"        => $this->sComment,
            'attributes'      => $orderAttributes,
            "sEsd"            => $esdOrder,
        );

        if ($dispatchId) {
            $variables["sDispatch"] = $this->sSYSTEM->sMODULES['sAdmin']->sGetPremiumDispatch($dispatchId);
        }
        if ($this->bookingId) {
            $variables['sBookingID'] = $this->bookingId;
        }

        if ($this->sgConfig->getSendOrderMail()) {
            try {
                $this->sendMail($variables);
            } catch (Exception $e) {
                $this->log(
                    "failed to send mail to customer for Shopware order number #{$orderNumber}",
                    ShopgateLogger::LOGTYPE_ERROR
                );
            }
        }

        // Check if voucher is affected
        $this->sTellFriend();

        // Save Billing and Shipping-Address to retrace in future
        for ($addressTextIndex = 1; $addressTextIndex <= 6; $addressTextIndex++) {
            if (empty($this->sUserData["billingaddress"]["text{$addressTextIndex}"])) {
                $this->sUserData["billingaddress"]["text{$addressTextIndex}"] = '';
            }
            if (empty($this->sUserData["shippingaddress"]["text{$addressTextIndex}"])) {
                $this->sUserData["shippingaddress"]["text{$addressTextIndex}"] = '';
            }
        }
        $this->sSaveBillingAddress($this->sUserData["billingaddress"], $orderID);
        $this->sSaveShippingAddress($this->sUserData["shippingaddress"], $orderID);

        // Completed - Garbage basket / temporary - order
        $this->sDeleteTemporaryOrder();

        $this->db->exec(
            $this->db->quoteInto(
                "DELETE FROM s_order_basket WHERE sessionID=?",
                array($this->sSYSTEM->sSESSION_ID)
            )
        );

        if (isset(Shopware()->Session()->sOrderVariables)) {
            $variables                             = Shopware()->Session()->sOrderVariables;
            $variables['sOrderNumber']             = $orderNumber;
            Shopware()->Session()->sOrderVariables = $variables;
        }

        $this->log(
            "> Shopware_Plugins_Backend_SgateShopgatePlugin_Components_SaveOrder::sSaveOrder #END -> Returning Shopware order number #{$orderNumber}",
            ShopgateLogger::LOGTYPE_DEBUG
        );

        return $orderNumber;
    } // End public function Order

    /**
     * @return Enlight_Components_Session_Namespace
     */
    private function getSession()
    {
        if ($this->session == null) {
            $this->session = Shopware()->Session();
        }

        return $this->session;
    }

    /**
     * Checks if the current order was send from a partner and returns
     * the partner code.
     *
     * @param int $userAffiliate affiliate flag of the user data.
     *
     * @return null|string
     */
    private function getPartnerCode($userAffiliate)
    {
        $isPartner = $this->getSession()->offsetGet("sPartner");
        if (!empty($isPartner)) {
            return $this->getSession()->offsetGet("sPartner");
        }

        if (empty($userAffiliate)) {
            return null;
        }

        // Get Partner code
        return $this->db->fetchOne(
            "SELECT idcode FROM s_emarketing_partner WHERE id = ?",
            array($userAffiliate)
        );
    }

    /**
     * send order confirmation mail
     *
     * @access public
     */
    public function sendMail($variables)
    {
        // 		$variables = Enlight()->Events()->filter('Shopware_Modules_Order_SendMail_FilterVariables', $variables, array('subject' => $this));

        $context = array(
            'sOrderDetails' => $variables["sOrderDetails"],

            'billingaddress'  => $variables["billingaddress"],
            'shippingaddress' => $variables["shippingaddress"],
            'additional'      => $variables["additional"],

            'sShippingCosts' => $variables["sShippingCosts"],
            'sAmount'        => $variables["sAmount"],
            'sAmountNet'     => $variables["sAmountNet"],

            'sOrderNumber' => $variables["ordernumber"],
            'sOrderDay'    => $variables["sOrderDay"],
            'sOrderTime'   => $variables["sOrderTime"],
            'sComment'     => $variables["sComment"],

            'attributes' => $variables["attributes"],
            'sCurrency'  => $this->sSYSTEM->sCurrency["currency"],

            'sLanguage' => $this->sSYSTEM->sLanguageData[$this->sSYSTEM->sLanguage]["isocode"],

            'sSubShop' => $this->sSYSTEM->sSubShop["id"],

            'sEsd' => $variables["sEsd"],
            'sNet' => $this->sNet,

        );

        // Support for individual paymentmeans with custom-tables
        if ($variables["additional"]["payment"]["table"]) {
            $paymentTable = $this->db->fetchRow(
                "SELECT * FROM {$variables["additional"]["payment"]["table"]}
                 WHERE userID=?",
                array($variables["additional"]["user"]["id"])
            );
            $context["sPaymentTable"] = $paymentTable;
        } else {
            $context["sPaymentTable"] = array();
        }

        if ($variables["sDispatch"]) {
            $context['sDispatch'] = $variables["sDispatch"];
        }

        if ($variables['sBookingID']) {
            $context['sBookingID'] = $variables["sBookingID"];
        }

        $mail = Shopware()->TemplateMail()->createMail('sORDER', $context);
        $mail->addTo($this->sUserData["additional"]["user"]["email"]);

        if (!$this->sSYSTEM->sCONFIG["sNO_ORDER_MAIL"]) {
            $mail->addBcc($this->sSYSTEM->sCONFIG['sMAIL']);
        }

        Enlight()->Events()->notify(
            'Shopware_Modules_Order_SendMail_BeforeSend',
            array('subject' => $this, 'mail' => $mail)
        );

        $mail->send();
    }

    /**
     * Save order billing address
     *
     * @access public
     */
    public function sSaveBillingAddress($address, $id)
    {
        $sql = "
		INSERT INTO s_order_billingaddress
		("
            . implode(",\n", $this->billingAddressFields) .
            ") VALUES ("
            . rtrim(str_repeat("?,\n", count($this->billingAddressFields)), ",\n") .
            ")";

        $sql   = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveBilling_FilterSQL',
            $sql,
            array('subject' => $this, 'address' => $address, 'id' => $id)
        );
        $array = array(
            $address["userID"],
            $id,
            $address["company"],
            $address["department"],
            $address["salutation"],
            $address["customernumber"],
            $address["firstname"],
            $address["lastname"],
            $address["street"],
            $address["zipcode"],
            $address["city"],
            $address["phone"],
            $address["countryID"],
            $address["stateID"],
            $address["ustid"],
        );

        // add street number for Shopware version below 5.0.0
        if (!$this->sgConfig->assertMinimumVersion('5.0.0')) {
            array_splice($array, array_search('street', $this->billingAddressFields) + 1, 0, $address['streetnumber']);
        }

        if (!$this->sgConfig->assertMinimumVersion('5.2.0')) {
            array_splice($array, array_search('phone', $this->billingAddressFields) + 1, 0, $address['fax']);
        }

        $array  = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveBilling_FilterArray',
            $array,
            array('subject' => $this, 'address' => $address, 'id' => $id)
        );
        $result = $this->db->query($sql, $array);

        //new attribute tables
        $billingID = $this->db->lastInsertId();
        $sql       =
            "INSERT INTO s_order_billingaddress_attributes (billingID, text1, text2, text3, text4, text5, text6) VALUES (?,?,?,?,?,?,?)";
        $sql       = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveBillingAttributes_FilterSQL',
            $sql,
            array('subject' => $this, 'address' => $address, 'id' => $id)
        );
        $array     = array(
            $billingID,
            $address["text1"],
            $address["text2"],
            $address["text3"],
            $address["text4"],
            $address["text5"],
            $address["text6"],
        );
        $array     = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveBillingAttributes_FilterArray',
            $array,
            array('subject' => $this, 'address' => $address, 'id' => $id)
        );
        $this->db->query($sql, $array);

        return $result;
    }

    /**
     * save order shipping address
     *
     * @access public
     */
    public function sSaveShippingAddress($address, $id)
    {
        $sql = "
		INSERT INTO s_order_shippingaddress
		("
            . implode(",\n", $this->shippingAddressFields) .
            ") VALUES ("
            . rtrim(str_repeat("?,\n", count($this->shippingAddressFields)), ",\n") .
            ")";

        $sql   = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveShipping_FilterSQL',
            $sql,
            array('subject' => $this, 'address' => $address, 'id' => $id)
        );
        $array = array(
            $address["userID"],
            $id,
            $address["company"],
            $address["department"],
            $address["salutation"],
            $address["firstname"],
            $address["lastname"],
            $address["street"],
            $address["zipcode"],
            $address["city"],
            $address["countryID"],
            $address["stateID"],
        );

        // add street number for Shopware version below 5.0.0
        if (!$this->sgConfig->assertMinimumVersion('5.0.0')) {
            array_splice($array, array_search('street', $this->shippingAddressFields) + 1, 0, $address['streetnumber']);
        }

        $array  = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveShipping_FilterArray',
            $array,
            array('subject' => $this, 'address' => $address, 'id' => $id)
        );
        $result = $this->db->query($sql, $array);

        //new attribute table
        $shippingId = $this->db->lastInsertId();
        $sql        =
            "INSERT INTO s_order_shippingaddress_attributes (shippingID, text1, text2, text3, text4, text5, text6) VALUES (?,?,?,?,?,?,?)";
        $sql        = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveShippingAttributes_FilterSQL',
            $sql,
            array('subject' => $this, 'address' => $address, 'id' => $id)
        );
        $array      = array(
            $shippingId,
            $address["text1"],
            $address["text2"],
            $address["text3"],
            $address["text4"],
            $address["text5"],
            $address["text6"],
        );
        $array      = Enlight()->Events()->filter(
            'Shopware_Modules_Order_SaveShippingAttributes_FilterArray',
            $array,
            array('subject' => $this, 'address' => $address, 'id' => $id)
        );
        $this->db->query($sql, $array);

        return $result;
    }

    /**
     * smarty modifier fill
     */
    public function smarty_modifier_fill($str, $width = 10, $break = "...", $fill = " ")
    {
        if (!is_scalar($break)) {
            $break = "...";
        }
        if (empty($fill) || !is_scalar($fill)) {
            $fill = " ";
        }
        if (empty($width) || !is_numeric($width)) {
            $width = 10;
        } else {
            $width = (int)$width;
        }
        if (!is_scalar($str)) {
            return str_repeat($fill, $width);
        }
        if (strlen($str) > $width) {
            $str = substr($str, 0, $width - strlen($break)) . $break;
        }
        if ($width > strlen($str)) {
            return $str . str_repeat($fill, $width - strlen($str));
        } else {
            return $str;
        }
    }

    /**
     * smarty modifier padding
     */
    public function smarty_modifier_padding($str, $width = 10, $break = "...", $fill = " ")
    {
        if (!is_scalar($break)) {
            $break = "...";
        }
        if (empty($fill) || !is_scalar($fill)) {
            $fill = " ";
        }
        if (empty($width) || !is_numeric($width)) {
            $width = 10;
        } else {
            $width = (int)$width;
        }
        if (!is_scalar($str)) {
            return str_repeat($fill, $width);
        }
        if (strlen($str) > $width) {
            $str = substr($str, 0, $width - strlen($break)) . $break;
        }
        if ($width > strlen($str)) {
            return str_repeat($fill, $width - strlen($str)) . $str;
        } else {
            return $str;
        }
    }

    /**
     * Check if this order could be refered to a previous recommendation
     *
     * @access public
     */
    public function sTellFriend()
    {
        $checkMail = $this->sUserData["additional"]["user"]["email"];

        $tmpSQL           = "
		SELECT * FROM s_emarketing_tellafriend WHERE confirmed=0 AND recipient=?
		";
        $checkIfUserFound = $this->db->fetchRow($tmpSQL, array($checkMail));
        if (count($checkIfUserFound)) {
            // User-Datensatz aktualisieren
            $updateUserFound = $this->db->query(
                "UPDATE s_emarketing_tellafriend SET confirmed=1 WHERE recipient=? ",
                array($checkMail)
            );
            // --

            // Daten �ber Werber fetchen
            $getWerberInfo = $this->db->fetchRow(
                "SELECT * FROM s_user, s_user_billingaddress
                 WHERE s_user_billingaddress.userID = s_user.id AND s_user.id=?",
                array($checkIfUserFound["sender"])
            );

            if (empty($getWerberInfo)) {
                // Benutzer nicht mehr vorhanden
                return;
            }

            $context = array(
                'customer'     => $getWerberInfo["firstname"] . " " . $getWerberInfo["lastname"],
                'user'         => $this->sUserData["billingaddress"]["firstname"] . " "
                    . $this->sUserData["billingaddress"]["lastname"],
                'voucherValue' => $this->sSYSTEM->sCONFIG['sVOUCHERTELLFRIENDVALUE'],
                'voucherCode'  => $this->sSYSTEM->sCONFIG['sVOUCHERTELLFRIENDCODE'],
            );

            $mail = Shopware()->TemplateMail()->createMail('sVOUCHER', $context);
            $mail->addTo($getWerberInfo["email"]);
            $mail->send();
        } // - if user found
    } // Tell-a-friend

    /**
     * Send status mail
     *
     * @param Enlight_Components_Mail $mail
     *
     * @return Enlight_Components_Mail
     */
    public function sendStatusMail(Enlight_Components_Mail $mail)
    {
        Enlight()->Events()->notify(
            'Shopware_Controllers_Backend_OrderState_Send_BeforeSend',
            array(
                'subject' => Shopware()->Front(),
                'mail'    => $mail,
            )
        );

        if (!empty(Shopware()->Config()->OrderStateMailAck)) {
            $mail->addBcc(Shopware()->Config()->OrderStateMailAck);
        }

        return $mail->send();
    }

    /**
     * Create status mail
     *
     * @param int    $orderId
     * @param int    $statusId
     * @param string $templateName
     *
     * @return Enlight_Components_Mail
     */
    public function createStatusMail($orderId, $statusId, $templateName = null)
    {
        $statusId = (int)$statusId;
        $orderId  = (int)$orderId;

        if (empty($templateName)) {
            $templateName = 'sORDERSTATEMAIL' . $statusId;
        }

        if (empty($orderId) || !is_numeric($statusId)) {
            return;
        }

        $order        = $this->getOrderForStatusMail($orderId);
        $orderDetails = $this->getOrderDetailsForStatusMail($orderId);

        if (!empty($order['dispatchID'])) {
            $dispatch = Shopware()->Db()->fetchRow(
                'SELECT name, description FROM s_premium_dispatch WHERE id=?',
                array(
                    $order['dispatchID'],
                )
            );
        }

        $user = $this->getCustomerInformationByOrderId($orderId);

        if (empty($order) || empty($orderDetails) || empty($user)) {
            return;
        }

        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $shopId     = is_numeric($order['language'])
            ? $order['language']
            : $order['subshopID'];
        $shop       = $repository->getActiveById($shopId);
        $shop->registerResources(Shopware()->Bootstrap());

        /* @var $mailModel \Shopware\Models\Mail\Mail */
        $mailModel =
            Shopware()->Models()->getRepository('Shopware\Models\Mail\Mail')->findOneBy(array('name' => $templateName));
        if (!$mailModel) {
            return;
        }

        $context = array(
            'sOrder'        => $order,
            'sOrderDetails' => $orderDetails,
            'sUser'         => $user,
        );

        if (!empty($dispatch)) {
            $context['sDispatch'] = $dispatch;
        }

        $result = Enlight()->Events()->notify(
            'Shopware_Controllers_Backend_OrderState_Notify',
            array(
                'subject'  => Shopware()->Front(),
                'id'       => $orderId,
                'status'   => $statusId,
                'mailname' => $templateName,
            )
        );

        if (!empty($result)) {
            $context['EventResult'] = $result->getValues();
        }

        $mail = Shopware()->TemplateMail()->createMail($templateName, $context, $shop);

        $return = array(
            'content'  => $mail->getPlainBodyText(),
            'subject'  => $mail->getPlainSubject(),
            'email'    => trim($user['email']),
            'frommail' => $mail->getFrom(),
            'fromname' => $mail->getFromName(),
        );

        $return = Enlight()->Events()->filter(
            'Shopware_Controllers_Backend_OrderState_Filter',
            $return,
            array(
                'subject'  => Shopware()->Front(),
                'id'       => $orderId,
                'status'   => $statusId,
                'mailname' => $templateName,
                'mail'     => $mail,
                'engine'   => Shopware()->Template(),
            )
        );

        $mail->clearSubject();
        $mail->setSubject($return['subject']);

        $mail->setBodyText($return['content']);

        $mail->clearFrom();
        $mail->setFrom($return['frommail'], $return['fromname']);

        $mail->addTo($return['email']);

        return $mail;
    }

    /**
     * Helper function which get formated order data for the passed order id.
     * This function is used if the order status changed and the status mail will be
     * send.
     *
     * @param $orderId
     *
     * @return mixed
     */
    private function getOrderForStatusMail($orderId)
    {
        $order      = $this->getOrderById($orderId);
        $attributes = $this->getOrderAttributes($orderId);
        unset($attributes['id']);
        unset($attributes['orderID']);
        $order['attributes'] = $attributes;

        return $order;
    }

    /**
     * Helper function which returns the attributes
     * of the passed order id.
     *
     * @param $orderId
     *
     * @return array|false
     */
    private function getOrderAttributes($orderId)
    {
        $attributes = $this->db->fetchRow(
            'SELECT * FROM s_order_attributes WHERE orderID = :orderId;',
            array('orderId' => $orderId)
        );

        return $attributes;
    }

    /**
     * Replacement for: Shopware()->Api()->Export()->sOrderCustomers(array('orderID' => $orderId));
     *
     * @param $orderId
     *
     * @return array|false
     */
    public function getCustomerInformationByOrderId($orderId)
    {
        $sql = <<<EOT
            SELECT
                `b`.`company` AS `billing_company`,
                `b`.`department` AS `billing_department`,
                `b`.`salutation` AS `billing_salutation`,
                `ub`.`customernumber`,
                `b`.`firstname` AS `billing_firstname`,
                `b`.`lastname` AS `billing_lastname`,
                `b`.`street` AS `billing_street`,
                `b`.`zipcode` AS `billing_zipcode`,
                `b`.`city` AS `billing_city`,
                `b`.`phone` AS `phone`,
                `b`.`phone` AS `billing_phone`,
                `b`.`fax` AS `fax`,
                `b`.`fax` AS `billing_fax`,
                `b`.`countryID` AS `billing_countryID`,
                `b`.`stateID` AS `billing_stateID`,
                `bc`.`countryname` AS `billing_country`,
                `bc`.`countryiso` AS `billing_countryiso`,
                `bca`.`name` AS `billing_countryarea`,
                `bc`.`countryen` AS `billing_countryen`,
                `b`.`ustid`,
                `ba`.`text1` AS `billing_text1`,
                `ba`.`text2` AS `billing_text2`,
                `ba`.`text3` AS `billing_text3`,
                `ba`.`text4` AS `billing_text4`,
                `ba`.`text5` AS `billing_text5`,
                `ba`.`text6` AS `billing_text6`,
                `b`.`orderID` as `orderID`,
                `s`.`company` AS `shipping_company`,
                `s`.`department` AS `shipping_department`,
                `s`.`salutation` AS `shipping_salutation`,
                `s`.`firstname` AS `shipping_firstname`,
                `s`.`lastname` AS `shipping_lastname`,
                `s`.`street` AS `shipping_street`,
                `s`.`zipcode` AS `shipping_zipcode`,
                `s`.`city` AS `shipping_city`,
                `s`.`stateID` AS `shipping_stateID`,
                `s`.`countryID` AS `shipping_countryID`,
                `sc`.`countryname` AS `shipping_country`,
                `sc`.`countryiso` AS `shipping_countryiso`,
                `sca`.`name` AS `shipping_countryarea`,
                `sc`.`countryen` AS `shipping_countryen`,
                `sa`.`text1` AS `shipping_text1`,
                `sa`.`text2` AS `shipping_text2`,
                `sa`.`text3` AS `shipping_text3`,
                `sa`.`text4` AS `shipping_text4`,
                `sa`.`text5` AS `shipping_text5`,
                `sa`.`text6` AS `shipping_text6`,
                `u`.*,
                   ub.birthday,
                   `g`.`id` AS `preisgruppe`,
                   `g`.`tax` AS `billing_net`
            FROM
                `s_order_billingaddress` as `b`
            LEFT JOIN `s_order_shippingaddress` as `s`
                ON `s`.`orderID` = `b`.`orderID`
            LEFT JOIN `s_user_billingaddress` as `ub`
                ON `ub`.`userID` = `b`.`userID`
            LEFT JOIN `s_user` as `u`
                ON `b`.`userID` = `u`.`id`
            LEFT JOIN `s_core_countries` as `bc`
                ON `bc`.`id` = `b`.`countryID`
            LEFT JOIN `s_core_countries` as `sc`
                ON `sc`.`id` = `s`.`countryID`
            LEFT JOIN `s_core_customergroups` as `g`
                ON `u`.`customergroup` = `g`.`groupkey`
            LEFT JOIN s_core_countries_areas bca
                ON bc.areaID = bca.id
            LEFT JOIN s_core_countries_areas sca
                ON sc.areaID = sca.id
            LEFT JOIN s_order_billingaddress_attributes ba
                ON b.id = ba.billingID
            LEFT JOIN s_order_shippingaddress_attributes sa
                ON s.id = sa.shippingID
            WHERE
                `b`.`orderID`=:orderId
EOT;

        $row = $this->db->fetchRow($sql, array('orderId' => $orderId));

        return $row;
    }

    /**
     * Replacement for: Shopware()->Api()->Export()->sGetOrders(array('orderID' => $orderId));
     *
     * @param int $orderId
     *
     * @return array|false
     */
    public function getOrderById($orderId)
    {
        $sql = <<<EOT
            SELECT
                `o`.`id` as `orderID`,
                `o`.`ordernumber`,
                `o`.`ordernumber` as `order_number`,
                `o`.`userID`,
                `o`.`userID` as `customerID`,
                `o`.`invoice_amount`,
                `o`.`invoice_amount_net`,
                `o`.`invoice_shipping`,
                `o`.`invoice_shipping_net`,
                `o`.`ordertime` as `ordertime`,
                `o`.`status`,
                `o`.`status` as `statusID`,
                `o`.`cleared` as `cleared`,
                `o`.`cleared` as `clearedID`,
                `o`.`paymentID` as `paymentID`,
                `o`.`transactionID` as `transactionID`,
                `o`.`comment`,
                `o`.`customercomment`,
                `o`.`net`,
                `o`.`net` as `netto`,
                `o`.`partnerID`,
                `o`.`temporaryID`,
                `o`.`referer`,
                o.cleareddate,
                o.cleareddate as cleared_date,
                o.trackingcode,
                o.language,
                o.currency,
                o.currencyFactor,
                o.subshopID,
                o.dispatchID,
                cu.id as currencyID,
                `c`.`name` as `cleared_name`,
                `c`.`description` as `cleared_description`,
                `s`.`name` as `status_name`,
                `s`.`description` as `status_description`,
                `p`.`description` as `payment_description`,
                `d`.`name` 		  as `dispatch_description`,
                `cu`.`name` 	  as `currency_description`
            FROM
                `s_order` as `o`
            LEFT JOIN `s_core_states` as `s`
                ON	(`o`.`status` = `s`.`id`)
            LEFT JOIN `s_core_states` as `c`
                ON	(`o`.`cleared` = `c`.`id`)
            LEFT JOIN `s_core_paymentmeans` as `p`
                ON	(`o`.`paymentID` = `p`.`id`)
            LEFT JOIN `s_premium_dispatch` as `d`
                ON	(`o`.`dispatchID` = `d`.`id`)
            LEFT JOIN `s_core_currencies` as `cu`
                ON	(`o`.`currency` = `cu`.`currency`)
            WHERE
                `o`.`id` = :orderId
EOT;

        $row = $this->db->fetchRow($sql, array('orderId' => $orderId));

        return $row;
    }

    /**
     * Replacement for: Shopware()->Api()->Export()->sOrderDetails(array('orderID' => $orderId));
     *
     * Returns order details for a given orderId
     *
     * @param int $orderId
     *
     * @return array
     */
    public function getOrderDetailsByOrderId($orderId)
    {
        $sql = <<<EOT
            SELECT
                `d`.`id` as `orderdetailsID`,
                `d`.`orderID` as `orderID`,
                `d`.`ordernumber`,
                `d`.`articleID`,
                `d`.`articleordernumber`,
                `d`.`price` as `price`,
                `d`.`quantity` as `quantity`,
                `d`.`price`*`d`.`quantity` as `invoice`,
                `d`.`name`,
                `d`.`status`,
                `d`.`shipped`,
                `d`.`shippedgroup`,
                `d`.`releasedate`,
                `d`.`modus`,
                `d`.`esdarticle`,
                `d`.`taxID`,
                `t`.`tax`,
                `d`.`tax_rate`,
                `d`.`esdarticle` as `esd`
            FROM
                `s_order_details` as `d`
            LEFT JOIN
                `s_core_tax` as `t`
            ON
                `t`.`id` = `d`.`taxID`
            WHERE
                `d`.`orderID` = :orderId
            ORDER BY
                `orderdetailsID` ASC
EOT;

        $rows = $this->db->fetchAll($sql, array('orderId' => $orderId));

        return $rows;
    }

    /**
     * Set payment status by order id
     *
     * @param int         $orderId
     * @param int         $paymentStatusId
     * @param bool        $sendStatusMail
     * @param string|null $comment
     */
    public function setPaymentStatus($orderId, $paymentStatusId, $sendStatusMail = false, $comment = null)
    {
        $sql              = 'SELECT `cleared` FROM `s_order` WHERE `id`=?;';
        $previousStatusId = Shopware()->Db()->fetchOne($sql, array($orderId));
        if ($paymentStatusId != $previousStatusId) {
            $sql = 'UPDATE `s_order` SET `cleared`=? WHERE `id`=?;';
            Shopware()->Db()->query($sql, array($paymentStatusId, $orderId));
            $sql = '
               INSERT INTO s_order_history (
                  orderID, userID, previous_order_status_id, order_status_id,
                  previous_payment_status_id, payment_status_id, comment, change_date )
                SELECT id, NULL, status, status, ?, ?, ?, NOW() FROM s_order WHERE id=?
            ';
            Shopware()->Db()->query($sql, array($previousStatusId, $paymentStatusId, $comment, $orderId));
            if ($sendStatusMail) {
                $mail = $this->createStatusMail($paymentStatusId, $comment, $orderId);
                if ($mail) {
                    $this->sendStatusMail($mail);
                }
            }
        }
    }

    /**
     * Set payment status by order id
     *
     * @param int         $orderId
     * @param int         $orderStatusId
     * @param bool        $sendStatusMail
     * @param string|null $comment
     */
    public function setOrderStatus($orderId, $orderStatusId, $sendStatusMail = false, $comment = null)
    {
        $sql              = 'SELECT `status` FROM `s_order` WHERE `id`=?;';
        $previousStatusId = Shopware()->Db()->fetchOne($sql, array($orderId));
        if ($orderStatusId != $previousStatusId) {
            $sql = 'UPDATE `s_order` SET `status`=? WHERE `id`=?;';
            Shopware()->Db()->query($sql, array($orderStatusId, $orderId));
            $sql = '
               INSERT INTO s_order_history (
                  orderID, userID, previous_order_status_id, order_status_id,
                  previous_payment_status_id, payment_status_id, comment, change_date )
                SELECT id, NULL, ?, ?, cleared, cleared, ?, NOW() FROM s_order WHERE id=?
            ';
            Shopware()->Db()->query($sql, array($previousStatusId, $orderStatusId, $comment, $orderId));
            if ($sendStatusMail) {
                $mail = $this->createStatusMail($orderStatusId, $comment, $orderId);
                if ($mail) {
                    $this->sendStatusMail($mail);
                }
            }
        }
    }

    /**
     * Checks if the order details table has the column 'ean'
     *
     * @return bool
     */
    public function orderDetailsHaveEan()
    {
        $dbConfig = Shopware()->Db()->getConfig();
        $result   = Shopware()->Db()->fetchAll(
            "SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_SCHEMA = '" . $dbConfig['dbname'] . "'
             AND TABLE_NAME = 's_order_details' AND COLUMN_NAME = 'ean'"
        );

        return !empty($result);
    }
}
