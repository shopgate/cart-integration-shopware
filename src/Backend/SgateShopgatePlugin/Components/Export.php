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

use Shopgate\Helpers\Attribute as AttributeHelper;
use Shopware\Bundle\SearchBundle\Condition\CategoryCondition;
use Shopware\Bundle\SearchBundle\Condition\CustomerGroupCondition;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\ProductContext;

class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export
{
    const CACHE_KEY_CUSTOMERGROUPS                  = 'customer_groups_ids';
    const CACHE_KEY_CATEGORY_PRODUCT_SORTING        = 'categories_product_sort_';
    const CACHE_KEY_STREAM_CATEGORY_PRODUCT_SORTING = 'stream_categories_product_sort_';

    /** @var \Shopware\Models\Shop\Shop */
    protected $shop;

    /** @var \Shopware\Models\Shop\Locale */
    protected $locale;

    /** @var Shopgate_Helper_Logging_Strategy_LoggingInterface */
    protected $logger;

    /** @var Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_ArticleInterface */
    protected $articleSortModel;

    /** @var array */
    protected $languageCategoryList = array();

    /** @var array */
    protected $languageCompleteCategoryList = array();

    /** @var sSystem */
    protected $system;

    /** @var AttributeHelper */
    protected $attributeHelper;

    /** @var null|phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface */
    protected $cacheInstance = null;

    /**
     * cache that can be used during export processes
     *
     * @var array
     */
    protected $exportCache = array();

    /**
     * article elements used in property export
     *
     * @var array
     */
    protected $elements = array();

    /**
     * @var array
     */
    protected $requestParams = array();

    /**
     * Id of the root category for the shopgate plugin
     *
     * @var array
     */
    protected $rootCategoryId;

    /**
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config
     */
    protected $config;

    /**
     * @param Shopgate_Helper_Logging_Strategy_LoggingInterface                         $logger
     * @param Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_ArticleInterface $articleSortModel
     * @param int                                                                       $rootCategoryId
     * @param array                                                                     $requestParams
     */
    public function __construct(
        Shopgate_Helper_Logging_Strategy_LoggingInterface $logger,
        Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_ArticleInterface $articleSortModel,
        $rootCategoryId = null,
        $requestParams = array()
    ) {
        $this->logger           = $logger;
        $this->articleSortModel = $articleSortModel;
        $this->requestParams    = $requestParams;
        $this->shop             = Shopware()->Models()->find("Shopware\Models\Shop\Shop", Shopware()->Shop()->getId());
        $this->rootCategoryId   = is_null($rootCategoryId)
            ? $this->shop->getCategory()->getId()
            : $rootCategoryId;
        $this->locale           = $this->shop->getLocale();
        $this->system           = Shopware()->System();
        $this->attributeHelper  = new AttributeHelper();
        $this->config           = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        $this->initLanguageCategoryList();
        $this->initLanguageCompleteCategoryList();
        $this->initCache();

        $this->logger->log(
            "languageCategorylist entries: " . count($this->languageCategoryList)
            . ' languageCompleteCategoryList entries: ' . count($this->languageCompleteCategoryList),
            Shopgate_Helper_Logging_Strategy_LoggingInterface::LOGTYPE_DEBUG
        );
    }

    /**
     * @param string      $name
     * @param string|null $namespace
     *
     * @return mixed
     */
    public function getTemplateText($name, $namespace = null)
    {
        $sql = "
			SELECT
				 IFNULL(`s2`.`value`, `s1`.`value`)
			FROM `s_core_snippets` AS `s1`
				LEFT JOIN `s_core_snippets` AS `s2` ON `s1`.namespace = `s2`.namespace
					AND `s1`.name = `s2`.name AND `s2`.localeId = {$this->locale->getId()}
					AND `s2`.shopId = {$this->shop->getId()}
			WHERE `s1`.`name` = '{$name}'
				AND `s1`.shopID = 1
				AND `s1`.localeID = {$this->locale->getId()}
		";

        if (!empty($namespace)) {
            $sql .= " AND `s1`.namespace = '{$namespace}'";
        }

        $text = Shopware()->Db()->fetchOne($sql);

        return $text;
    }

    /**
     *
     */
    protected function initLanguageCategoryList()
    {
        $this->buildLanguageCategoryList($this->rootCategoryId);
    }

    /**
     *
     */
    protected function initLanguageCompleteCategoryList()
    {
        $this->buildLanguageCompleteCategoryList($this->rootCategoryId);
    }

    /**
     * @param $parentID
     */
    protected function buildLanguageCategoryList($parentID)
    {
        $qry = "
			SELECT id
			FROM s_categories
			WHERE parent = {$parentID}
			  AND active = 1";

        $result = Shopware()->Db()->fetchAll($qry);

        foreach ($result as $row) {
            $this->languageCategoryList[] = $row["id"];

            if ($row["id"] != $parentID) {
                $this->buildLanguageCategoryList($row["id"]);
            }
        }
    }

    /**
     * @param $parentID
     */
    protected function buildLanguageCompleteCategoryList($parentID)
    {
        $qry = "
			SELECT id
			FROM s_categories
			WHERE parent = {$parentID}";

        $result = Shopware()->Db()->fetchAll($qry);

        foreach ($result as $row) {
            $this->languageCompleteCategoryList[] = $row["id"];

            if ($row["id"] != $parentID) {
                $this->buildLanguageCompleteCategoryList($row["id"]);
            }
        }
    }

    /**
     * checks if category is associated to the current shop and active
     *
     * @param $categoryId
     *
     * @return bool
     */
    public function checkCategory($categoryId)
    {
        return in_array($categoryId, $this->languageCategoryList);
    }

    /**
     * checks if category is associated to the current shop
     *
     * @param $categoryId
     *
     * @return bool
     */
    public function checkCompleteCategory($categoryId)
    {
        return in_array($categoryId, $this->languageCompleteCategoryList);
    }

    /**
     * @return phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface
     */
    protected function getCacheInstance()
    {
        if (!$this->cacheInstance) {
            $namespace = 'phpFastCache';
            if (!class_exists('phpFastCache\CacheManager')) {
                $namespace = 'Phpfastcache';
            }

            $cacheManagerClass = "{$namespace}\CacheManager";

            $this->cacheInstance = $cacheManagerClass::getInstance(
                'files', array(
                    'path' => rtrim($this->config->getCacheFolderPath())
                )
            );
        }

        return $this->cacheInstance;
    }

    /**
     * handles cache persistence for one catalog export
     */
    protected function initCache()
    {
        if (!isset($this->requestParams['offset']) || $this->requestParams['offset'] == 0) {
            $this->getCacheInstance()->clear();
        }
    }

    /**
     * @param string $key
     * @param string | array $value
     */
    protected function setExportCache($key, $value)
    {
        $instance   = $this->getCacheInstance();
        $cachedItem = $instance->getItem($key);
        $cachedData = $cachedItem->isHit() ? $cachedItem->get() : [];

        if ($cachedData && is_array($value)) {
            $cachedData[key($value)] = $value[key($value)];
        } else {
            $cachedData = $value;
        }

        $cachedItem->set($cachedData);
        $instance->save($cachedItem);
    }

    /**
     * @param string        $key
     * @param string | null $subKey
     *
     * @return string | array | null
     */
    protected function getExportCache($key, $subKey = null)
    {
        $instance   = $this->getCacheInstance();
        $cachedItem = $instance->getItem($key);
        if (!$cachedItem->isHit()) {
            return null;
        }

        $cachedData = $cachedItem->get();
        return $subKey
            ? isset($cachedData[$subKey])
                ? $cachedData[$subKey]
                : null
            : $cachedData;
    }

    /**
     * @param $articleId
     * @param $categoryId
     *
     * @return int | null
     */
    public function getArticleOrderIndex($articleId, $categoryId)
    {
        $cacheKey = self::CACHE_KEY_CATEGORY_PRODUCT_SORTING . $categoryId;
        $cache    = $this->getExportCache($cacheKey);
        if ($cache === null) {
            $cache = array();
            ShopgateLogger::getInstance()->log("Start creating Cache {$cacheKey}", ShopgateLogger::LOGTYPE_DEBUG);

            $version = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Version();
            if ($version->assertMinimum('5.0.0')) {
                $articles = $this->getAllArticlesByCategoryIdNew($categoryId);
                $i        = 0;
                $maxSort  = count($articles) + 1;
                foreach ($articles as $article) {
                    $cache[$article] = $maxSort - $i++;
                }
            } else {
                $articles = $this->getAllArticlesByCategoryIdOld($categoryId);
                $i        = 0;
                $maxSort  = count($articles) + 1;
                foreach ($articles as $article) {
                    $cache[$cacheKey][$article['articleID']] = ($maxSort - $i++);
                }
            }

            ShopgateLogger::getInstance()->log(
                "Created Cache {$cacheKey} with " .
                count($cache[$cacheKey] ?: []) . " entries",
                ShopgateLogger::LOGTYPE_DEBUG
            );

            $this->setExportCache($cacheKey, $cache);
        }

        return isset($cache[$articleId]) ? $cache[$articleId] : null;
    }

    /**
     * returns customer group id by group key
     *
     * @param $customerGroupKey
     *
     * @return int | null
     */
    public function getCustomerGroupIdByKey($customerGroupKey)
    {
        $cacheKey = self::CACHE_KEY_CUSTOMERGROUPS . $customerGroupKey;
        $cache    = $this->getExportCache($cacheKey);
        if ($cache === null) {
            $cache           = array();
            $groupRepository = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group');
            $customerGroup   = $groupRepository->findOneBy(array('key' => $customerGroupKey));

            if ($customerGroup instanceof \Shopware\Models\Customer\Group) {
                $cache = $customerGroup->getId();
            }

            $this->setExportCache($cacheKey, $cache);
        }

        return $cache;
    }

    /**
     * returns price model for an article detail
     *
     * @param int    $articleDetailsId
     * @param string $groupKey
     *
     * @return \Shopware\Models\Article\Price \ null
     */
    public function getPriceModel($articleDetailsId, $groupKey)
    {
        $dql = "SELECT p FROM \Shopware\Models\Article\Price p
				WHERE p.articleDetailsId = :adID
				AND p.customerGroupKey = :groupKey
				ORDER BY p.from ASC";

        $childPriceModel = Shopware()->Models()->createQuery($dql)
                                     ->setMaxResults(1)
                                     ->setParameter("adID", $articleDetailsId)
                                     ->setParameter("groupKey", $groupKey)
                                     ->getOneOrNullResult();

        return $childPriceModel;
    }

    /**
     * Returns all article elements, used for property export.
     *
     * @return array
     */
    public function getArticleElements()
    {
        if (empty($this->elements)) {
            $this->elements = $this->attributeHelper->getConfiguredAttributes();
        }

        return $this->elements;
    }

    /**
     * returns array with all active categories of the current subshop
     *
     * @return array
     */
    public function getLanguageCategoryList()
    {
        return $this->languageCategoryList;
    }

    /**
     * returns array with all categories of the current subshop
     *
     * @return array
     */
    public function getLanguageCompleteCategoryList()
    {
        return $this->languageCompleteCategoryList;
    }

    /**
     * Exports orderindex of all products in specified category for shopware 4.x
     *
     * @param $categoryId
     *
     * @return array
     */
    public function getAllArticlesByCategoryIdOld($categoryId)
    {
        //used for the different sorting parameters. In default case the s_articles table is sorted, so we can set this as default
        $sqlFromPath = "
            FROM s_articles AS a
            INNER JOIN s_articles_details AS aDetails
                ON aDetails.id = a.main_detail_id
        ";

        $groupBy = 'a.id';
        $orderBy = $this->system->sCONFIG['sORDERBYDEFAULT'] . ', a.id DESC';

        if (strpos($orderBy, 'price') !== false) {
            $select_price = "
                (
                    SELECT IFNULL(p.price, p2.price) as min_price
                    FROM s_articles_details d

                    LEFT JOIN s_articles_prices p
                    ON p.articleDetailsID=d.id
                    AND p.pricegroup='{$this->system->sUSERGROUP}'
                    AND p.to='beliebig'

                    LEFT JOIN s_articles_prices p2
                    ON p2.articledetailsID=d.id
                    AND p2.pricegroup='EK'
                    AND p2.to='beliebig'

                    WHERE d.articleID=a.id

                    ORDER BY min_price
                    LIMIT 1
                ) * ( (100 - IFNULL(cd.discount, 0) ) / 100)
            ";
            $join_price   = "
                LEFT JOIN s_core_customergroups cg
                    ON cg.groupkey = '{$this->system->sUSERGROUP}'

                LEFT JOIN s_core_pricegroups_discounts cd
                    ON a.pricegroupActive=1
                    AND cd.groupID=a.pricegroupID
                    AND cd.customergroupID=cg.id
                    AND cd.discountstart=(
                        SELECT MAX(discountstart)
                        FROM s_core_pricegroups_discounts
                        WHERE groupID=a.pricegroupID
                        AND customergroupID=cg.id
                    )
            ";
        } else {
            $select_price = 'IFNULL(p.price, p2.price)';
            $join_price   = '';
        }

        $supplierSQL    = "";
        $addFilterSQL   = "";
        $addFilterWhere = "";

        $version                 = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Version();
        $articlesCategoriesTable =
            $version->assertMinimum('4.1.0') ? "s_articles_categories_ro" : "s_articles_categories";

        $markNew              = (int)$this->system->sCONFIG['sMARKASNEW'];
        $topSeller            = (int)$this->system->sCONFIG['sMARKASTOPSELLER'];
        $now                  = Shopware()->Db()->quote(date('Y-m-d'));
        $currentCustomerGroup = $this->system->sUSERGROUPDATA['id'];

        $sql = "
            SELECT
                STRAIGHT_JOIN
                a.id as articleID,
                a.laststock,
                a.taxID,
                a.pricegroupID,
                a.pricegroupActive,
                a.notification as notification,
                a.datum,
                a.description AS description,
                a.description_long,
                a.name AS articleName,
                a.topseller as highlight,
                (a.configurator_set_id IS NOT NULL) as sConfigurator,


                aDetails.id AS articleDetailsID,
                aDetails.ordernumber,
                aDetails.releasedate,
                aDetails.shippingfree,
                aDetails.shippingtime,
                aDetails.minpurchase,
                aDetails.purchasesteps,
                aDetails.maxpurchase,
                aDetails.purchaseunit,
                aDetails.referenceunit,
                aDetails.unitID,
                aDetails.weight,
                aDetails.additionaltext,
                aDetails.instock,
                aDetails.sales,
                IF(aDetails.sales>=$topSeller,1,0) as topseller,
                IF(aDetails.releasedate>$now,1,0) as sUpcoming,
                IF(aDetails.releasedate>$now, aDetails.releasedate, '') as sReleasedate,

                aSupplier.name AS supplierName,
                aSupplier.img AS supplierImg,

                aTax.tax,

                aAttributes.attr1,
                aAttributes.attr2,
                aAttributes.attr3,
                aAttributes.attr4,
                aAttributes.attr5,
                aAttributes.attr6,
                aAttributes.attr7,
                aAttributes.attr8,
                aAttributes.attr9,
                aAttributes.attr10,
                aAttributes.attr11,
                aAttributes.attr12,
                aAttributes.attr13,
                aAttributes.attr14,
                aAttributes.attr15,
                aAttributes.attr16,
                aAttributes.attr17,
                aAttributes.attr18,
                aAttributes.attr19,
                aAttributes.attr20,

                $select_price as price,

                IF(p.pseudoprice,p.pseudoprice,p2.pseudoprice) as pseudoprice,
                IFNULL(p.pricegroup,IFNULL(p2.pricegroup,'EK')) as pricegroup,
                IFNULL((SELECT 1 FROM s_articles_details WHERE articleID=a.id AND kind=2 LIMIT 1), 0) as variants,
                IFNULL((SELECT 1 FROM s_articles_esd WHERE articleID=a.id LIMIT 1), 0) as esd,
                IF(DATEDIFF($now, a.datum) <= $markNew,1,0) as newArticle,

                (
                    SELECT CONCAT(AVG(points), '|',COUNT(*)) as votes
                    FROM   s_articles_vote
                    WHERE  s_articles_vote.active=1
                    AND    s_articles_vote.articleID = a.id
                ) AS sVoteAverange

            $sqlFromPath

            INNER JOIN $articlesCategoriesTable ac
              ON  ac.articleID = a.id
              AND ac.categoryID = $categoryId

            INNER JOIN s_categories c
                ON  c.id = ac.categoryID
                AND c.active = 1

            JOIN s_articles_attributes AS aAttributes
              ON aAttributes.articledetailsID = aDetails.id

            JOIN s_core_tax AS aTax
              ON aTax.id = a.taxID

            LEFT JOIN s_articles_avoid_customergroups ag
              ON ag.articleID = a.id
              AND ag.customergroupID = {$currentCustomerGroup}

            LEFT JOIN s_articles_supplier AS aSupplier
              ON aSupplier.id = a.supplierID

            $addFilterSQL

            LEFT JOIN s_articles_prices p
              ON p.articleDetailsID = aDetails.id
              AND p.pricegroup = '{$this->system->sUSERGROUP}'
              AND p.to = 'beliebig'

            LEFT JOIN s_articles_prices p2
              ON p2.articledetailsID = aDetails.id
              AND p2.pricegroup = 'EK'
              AND p2.to = 'beliebig'

            $join_price

            WHERE ag.articleID IS NULL
            AND a.active=1

            $addFilterWhere
            $supplierSQL

            GROUP BY $groupBy
            ORDER BY $orderBy
        ";

        $sql      = Enlight()->Events()->filter(
            'Shopware_Modules_Articles_sGetArticlesByCategory_FilterSql',
            $sql,
            array('subject' => $this, 'id' => $categoryId)
        );
        $articles = Shopware()->Db()->fetchAssoc($sql);

        if (empty($articles)) {
            return array();
        }

        return $articles;
    }

    /**
     * Exports orderindex of all products in specified category for shopware5
     *
     * @param $categoryId
     *
     * @return array
     */
    protected function getAllArticlesByCategoryIdNew($categoryId)
    {
        return $this->articleSortModel->getSortedArticleResultsByCategoryId($categoryId);
    }

    /**
     * Returns the Ids and sort order of streamcategories for an article.
     * Available since Shopware 5.2
     *
     * @param int $articleId
     *
     * @return array
     */
    public function getStreamCategories($articleId)
    {
        $catIds            = array();
        $categoryComponent = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Category(
            $this->getCacheInstance()
        );
        $streamCategories  = $categoryComponent->getStreamCategories($catIds, $this->rootCategoryId);

        foreach ($streamCategories as $categoryId => $productStreamId) {
            $products = $this->getStreamProducts($categoryId, $productStreamId);
            if (isset($products[$articleId])) {
                $catIds[$categoryId] = array(
                    'id'         => $categoryId,
                    'sort_order' => $products[$articleId],
                );
            }
        }

        return $catIds;
    }

    /**
     * returns products of a product stream
     *
     * @param int $categoryId
     * @param int $productStreamId
     *
     * @return array
     */
    public function getStreamProducts($categoryId, $productStreamId)
    {
        $version = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Version();
        if (!$version->assertMinimum('5.0.0')) {
            return array();
        }

        $cacheKey = self::CACHE_KEY_STREAM_CATEGORY_PRODUCT_SORTING . $categoryId;
        $cache    = $this->getExportCache($cacheKey);

        if ($cache === null) {
            $cache = array();
            ShopgateLogger::getInstance()->log("## getStreamProducts: {$categoryId}", ShopgateLogger::LOGTYPE_DEBUG);

            /** @var Shopware\Components\ProductStream\Repository $streamRepo */
            $streamRepo       = Shopware()->Container()->get('shopware_product_stream.repository');
            $criteria         = new Criteria();
            $shopId           = Shopware()->Shop()->getId();
            $currencyId       = Shopware()->Shop()->getCurrency()->getId();
            $customerGroupKey = ContextService::FALLBACK_CUSTOMER_GROUP;

            /** @var ProductContext|ShopContext $context */
            $context = !$version->assertMinimum('5.2.0')
                ? Shopware()->Container()->get('shopware_storefront.context_service')
                            ->createProductContext($shopId, $currencyId, $customerGroupKey)
                : Shopware()->Container()->get('shopware_storefront.context_service')
                            ->createShopContext($shopId, $currencyId, $customerGroupKey);

            $criteria->addBaseCondition(
                new CustomerGroupCondition(array($context->getCurrentCustomerGroup()->getId()))
            );
            $baseCategoryId = $context->getShop()->getCategory()->getId();
            $criteria->addBaseCondition(
                new CategoryCondition(array($baseCategoryId))
            );
            $criteria->limit(5000);

            $streamRepo->prepareCriteria($criteria, $productStreamId);

            $result = Shopware()->Container()->get('shopware_search.product_number_search')
                                ->search($criteria, $context);

            $index      = 0;
            $totalCount = $result->getTotalCount();
            foreach ($result->getProducts() as $product) {
                $cache[$product->getId()] = ($totalCount - $index--);
            }

            ShopgateLogger::getInstance()->log("## Product Count: {$totalCount}", ShopgateLogger::LOGTYPE_DEBUG);

            $this->setExportCache($cacheKey, $cache);
        }

        return $cache;
    }
}
