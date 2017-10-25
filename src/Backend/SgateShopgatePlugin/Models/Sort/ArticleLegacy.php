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
 * All constants in class StoreFrontCriteriaFactory were removed in Shopware 5.3
 */
class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_ArticleLegacy
    implements Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_ArticleInterface
{
    /** @var int */
    private $defaultListingSorting;

    /**
     * @param int $defaultListingSorting
     */
    public function __construct($defaultListingSorting)
    {
        $this->defaultListingSorting = $defaultListingSorting;
    }

    public function getArticleOrderBySQLSnippetFor($categoryId)
    {
        switch ($this->defaultListingSorting) {
            case Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactory::SORTING_RELEASE_DATE:
                $sorting = 'Order By product.datum DESC, product.changetime DESC, product.id DESC';
                break;
            case Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactory::SORTING_POPULARITY:
                $sorting = 'Order By topSeller.sales DESC, topSeller.article_id DESC';
                break;
            case Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactory::SORTING_CHEAPEST_PRICE:
                $sorting = 'Order By cheapest_price ASC, product.id ASC';
                break;
            case Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactory::SORTING_HIGHEST_PRICE:
                $sorting = 'Order By cheapest_price DESC, product.id DESC';
                break;
            case Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactory::SORTING_PRODUCT_NAME_ASC:
                $sorting = 'Order By product.name ASC, product.id ASC';
                break;
            case Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactory::SORTING_PRODUCT_NAME_DESC:
                $sorting = 'Order By product.name DESC, product.id DESC';
                break;
            case Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactory::SORTING_SEARCH_RANKING:
                $sorting = 'Order By searchTable.ranking DESC, product.id DESC';
                break;
            default:
                $sorting = '';
        }

        return $sorting;
    }

    /**
     * @param int $categoryId
     *
     * @return array
     */
    public function getSortedArticleResultsByCategoryId($categoryId)
    {
        $fallbackCustomerGroup = Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService::FALLBACK_CUSTOMER_GROUP;
        $currentCustomerGroup  = Shopware()->System()->sUSERGROUPDATA['key'];
        $customerGroupIds      = Shopware()->System()->sUSERGROUPDATA['id'];

        $sql = "SELECT
                  SQL_CALC_FOUND_ROWS product.id as __product_id,
                  variant.id as __variant_id,
                  variant.ordernumber as __variant_ordernumber,
                  MIN(ROUND(defaultPrice.price * ((100 - IFNULL(priceGroup.discount, 0)) / 100) * ((tax.tax + 100) / 100) * 1, 2)) as cheapest_price
                FROM s_articles product
                    INNER JOIN s_articles_details variant ON variant.id = product.main_detail_id
                      AND variant.active = 1
                      AND product.active = 1
                    INNER JOIN s_articles_attributes productAttribute ON productAttribute.articledetailsID = variant.id
                    INNER JOIN s_core_tax tax ON tax.id = product.taxID
                    INNER JOIN s_articles_categories_ro productCategory ON productCategory.articleID = product.id
                      AND productCategory.categoryID IN ('{$categoryId}')
                    LEFT JOIN s_articles_avoid_customergroups avoidCustomerGroup ON avoidCustomerGroup.articleID = product.id
                      AND avoidCustomerGroup.customerGroupId IN ('{$customerGroupIds}')
                    INNER JOIN s_articles_details availableVariant ON availableVariant.articleID = product.id
                      AND availableVariant.active = 1
                    LEFT JOIN s_articles_top_seller_ro topSeller ON topSeller.article_id = product.id
                    INNER JOIN s_articles_prices defaultPrice ON defaultPrice.articledetailsID = availableVariant.id
                      AND defaultPrice.pricegroup = '{$fallbackCustomerGroup}'
                      AND defaultPrice.from = 1
                    LEFT JOIN s_articles_prices customerPrice ON customerPrice.articleID = product.id
                      AND customerPrice.pricegroup = '{$currentCustomerGroup}'
                      AND customerPrice.from = 1
                      AND availableVariant.id = customerPrice.articledetailsID
                    LEFT JOIN s_core_pricegroups_discounts priceGroup ON priceGroup.groupID = product.pricegroupID
                      AND priceGroup.discountstart = 1
                      AND priceGroup.customergroupID = '{$customerGroupIds}'
                      AND product.pricegroupActive = 1
                  WHERE avoidCustomerGroup.articleID IS NULL
                GROUP BY product.id";

        $sql    .= ' ' . $this->getArticleOrderBySQLSnippetFor($categoryId);
        $query  = Shopware()->Db()->query($sql);
        $result = array();

        while ($row = $query->fetch()) {
            $result[] = $row['__product_id'];
        }

        return $result;
    }
}
