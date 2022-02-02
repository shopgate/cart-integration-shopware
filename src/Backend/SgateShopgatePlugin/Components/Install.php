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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Install
{
    /**
     * request url
     */
    const URL_TO_UPDATE_SHOPGATE = 'https://api.shopgate.com/log';
    /**
     * interface installation action
     */
    const TYPE_INSTALL = "interface_install";
    /**
     * internal shopgate system identifier
     */
    const INTERNAL_SHOPSYSTEM_ID = 213;

    /**
     * shopware system object
     */
    protected $sSYSTEM;

    /**
     * reference date
     */
    protected $date;

    /**
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Category
     */
    protected $categoryComponent = null;

    /**
     * prepare stuff
     */
    public function __construct()
    {
        $this->sSYSTEM           = Shopware()->System();
        $this->date              = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . '-1 months'));
        $this->categoryComponent = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Category();
    }

    /**
     * build registration data and call
     */
    public function updateShopgateInstall($type = self::TYPE_INSTALL)
    {
        $subshops = array();

        $sql = "
			SELECT shop.*, cur.currency
			FROM s_core_shops as shop
			LEFT JOIN s_core_currencies as cur ON cur.id = shop.currency_id
		";

        foreach (Shopware()->Db()->fetchAll($sql) as $subshop) {
            $rootCatId = $subshop['category_id'];
            $catIds    = $this->categoryComponent->getCategories(array(), $rootCatId);

            $domains = explode("\n", $subshop['host']);
            $domain  = trim(reset($domains));
            $url     = (!empty($domain))
                ? $domain
                : $this->sSYSTEM->sPathBase;

            $subshops[] = array(
                'uid'              => $subshop['id'],
                'url'              => $url,
                'name'             => $subshop['name'],
                'contact_name'     => '',
                'contact_phone'    => '',
                'contact_email'    => $this->sSYSTEM->sCONFIG['sMAIL'],
                'stats_items'      => $this->getItems($catIds),
                'stats_categories' => count($catIds),
                'stats_orders'     => $this->getOrders($subshop['id']),
                'stats_acs'        => $this->calculateAverage($subshop['id']),
                'stats_currency'   => $subshop['currency'],
            );
        }

        $data = array(
            'action'              => $type,
            'uid'                 => $this->getUid(),
            'plugin_version'      => Shopware()->Plugins()->Backend()->SgateShopgatePlugin()->getVersion(),
            'shopping_system_id'  => self::INTERNAL_SHOPSYSTEM_ID,
            'stats_unique_visits' => $this->getVisitors(),
            'stats_mobile_visits' => 0,
            'subshops'            => $subshops,
        );

        try {
            $client = new Zend_Http_Client(self::URL_TO_UPDATE_SHOPGATE);
            $client->setParameterPost($data);
            $client->request(Zend_Http_Client::POST);
        } catch (Exception $e) {
            $this->log("Shopgate_Framework Message: " . self::URL_TO_UPDATE_SHOPGATE . " could not be reached.");
        }
    }

    /**
     * create log file entries
     *
     * @param        $msg
     * @param string $type
     *
     * @return bool
     */
    protected function log($msg, $type = ShopgateLogger::LOGTYPE_ERROR)
    {
        return ShopgateLogger::getInstance()->log($msg, $type);
    }

    /**
     * get uid to clarify identification in shopgate system
     *
     * @return string
     */
    protected function getUid()
    {
        $host      =
            rtrim($this->sSYSTEM->sCONFIG["sHOST"], '/') . '/' . trim($this->sSYSTEM->sCONFIG["sBASEPATH"], '/');
        $accountId = $this->sSYSTEM->sCONFIG['sACCOUNTID'];

        return sha1(rtrim($host, '/') . $accountId);
    }

    /**
     * get product count by categories
     *
     * @param array $categories
     *
     * @return int
     */
    public function getItems($categories)
    {
        $sql = "
			SELECT count(DISTINCT d.id)
			FROM s_articles_details AS d
				INNER JOIN s_articles AS a ON a.id = d.articleID AND a.active = 1 AND a.mode = 0
				INNER JOIN s_articles_categories AS ac ON ac.articleID = a.id
			WHERE ac.categoryID in ('" . implode("','", array_keys($categories)) . "')
		";

        return Shopware()->Db()->fetchOne($sql);
    }

    /**
     * get orders count unfiltered
     *
     * @param int $shopId
     *
     * @return int
     */
    protected function getOrders($shopId)
    {
        $sql =
            "SELECT count(id) FROM s_order WHERE ordertime >= '" . $this->date . "' AND subshopID = '" . $shopId . "'";

        return Shopware()->Db()->fetchOne($sql);
    }

    /**
     * @param int $shopId
     *
     * @return float
     */
    protected function calculateAverage($shopId)
    {
        $sql = "SELECT IFNULL(AVG(invoice_amount), 0) FROM s_order WHERE subshopID = '" . $shopId . "'";

        return round((float)Shopware()->Db()->fetchOne($sql), 2);
    }

    /**
     * get visitor data unfiltered
     *
     * @return int
     */
    protected function getVisitors()
    {
        $sql = "SELECT IFNULL(SUM(uniquevisits), 0) FROM s_statistics_visitors WHERE datum >= '" .
            date('Y-m-d', strtotime($this->date)) . "'";

        return Shopware()->Db()->fetchOne($sql);
    }
}
