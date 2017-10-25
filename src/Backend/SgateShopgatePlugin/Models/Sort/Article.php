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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_Article
    implements Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_ArticleInterface
{
    /** @var \Shopware\Bundle\StoreFrontBundle\Service\CustomSortingServiceInterface */
    private $customSortingService;

    /** @var \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface */
    private $shopContext;

    /** @var \Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactoryInterface */
    private $criteriaFactory;

    /** @var \Shopware\Bundle\SearchBundleDBAL\QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var Enlight_Controller_Request_Request */
    private $requestHttpFake;

    /**
     * @param \Shopware\Bundle\StoreFrontBundle\Service\CustomSortingServiceInterface $customSortingService
     * @param \Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactoryInterface        $criteriaFactory
     * @param \Shopware\Bundle\SearchBundleDBAL\QueryBuilderFactoryInterface          $queryBuilderFactory
     * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface           $shopContext
     * @param Enlight_Controller_Request_Request                                      $requestHttpFake
     */
    public function __construct(
        \Shopware\Bundle\StoreFrontBundle\Service\CustomSortingServiceInterface $customSortingService,
        \Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactoryInterface $criteriaFactory,
        \Shopware\Bundle\SearchBundleDBAL\QueryBuilderFactoryInterface $queryBuilderFactory,
        \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $shopContext,
        Enlight_Controller_Request_Request $requestHttpFake
    ) {
        $this->shopContext          = $shopContext;
        $this->customSortingService = $customSortingService;
        $this->criteriaFactory      = $criteriaFactory;
        $this->queryBuilderFactory  = $queryBuilderFactory;
        $this->requestHttpFake      = $requestHttpFake;
    }

    public function getArticleOrderBySQLSnippetFor($categoryId)
    {
        $default = $this->getDefaultCategorySorting($categoryId);
        if (empty($default)) {
            return '';
        }

        $this->requestHttpFake->setParam('sSort', $default->getId());

        return $this->getPlainOrderBySqlFromCriteria($this->getListingCriteria());
    }

    /**
     * @param string $categoryId
     *
     * @return \Shopware\Bundle\SearchBundleDBAL\QueryBuilder
     */
    public function getArticleOrderedQueryObject($categoryId)
    {
        $default = $this->getDefaultCategorySorting($categoryId);
        if (empty($default)) {
            return null;
        }

        $this->requestHttpFake->setParam('sSort', $default->getId());

        return $this->getQueryBuilderFromCriteria($this->getListingCriteria());
    }

    /**
     * @param Shopware\Bundle\SearchBundleDBAL\QueryBuilder $queryBuilder
     *
     * @return string
     */
    protected function getPlainOrderBySqlFrom(Shopware\Bundle\SearchBundleDBAL\QueryBuilder $queryBuilder)
    {
        $parts = $queryBuilder->getQueryParts();

        $sortQuery = '';
        if (!empty($parts['orderBy'])) {
            $sortQuery .= 'Order By ';
            $sortQuery .= implode(', ', $parts['orderBy']);
        }

        return $sortQuery;
    }

    /**
     * This logic comes from engine/Shopware/Controllers/Frontend/Listing.php method loadCategoryListing
     *
     * @param int $categoryId
     *
     * @return \Shopware\Models\Search\CustomSorting
     */
    protected function getDefaultCategorySorting($categoryId)
    {
        $categorySortings = $this->customSortingService->getSortingsOfCategories(
            array($categoryId),
            $this->shopContext
        );

        /** @var \Shopware\Models\Search\CustomSorting[] $categorySortings */
        $categorySortings = array_shift($categorySortings);

        /** @var \Shopware\Models\Search\CustomSorting $default */
        $default = array_shift($categorySortings);

        return $default;
    }

    /**
     * Settings for default listing criteria can be found in Shopware backend
     * Configuration => Basic settings => Frontend => Filter / Sorting => Sortings
     *
     * @return \Shopware\Bundle\SearchBundle\Criteria
     */
    protected function getListingCriteria()
    {
        return $this->criteriaFactory->createListingCriteria($this->requestHttpFake, $this->shopContext);
    }

    /**
     * @param \Shopware\Bundle\SearchBundle\Criteria $criteria
     *
     * @return string
     */
    protected function getPlainOrderBySqlFromCriteria(\Shopware\Bundle\SearchBundle\Criteria $criteria)
    {
        $queryBuilder = $this->queryBuilderFactory->createQueryWithSorting($criteria, $this->shopContext);

        return $this->getPlainOrderBySqlFrom($queryBuilder);
    }

    /**
     * @param \Shopware\Bundle\SearchBundle\Criteria $criteria
     *
     * @return \Shopware\Bundle\SearchBundleDBAL\QueryBuilder
     */
    protected function getQueryBuilderFromCriteria(\Shopware\Bundle\SearchBundle\Criteria $criteria)
    {
        $queryBuilder = $this->queryBuilderFactory->createQueryWithSorting($criteria, $this->shopContext);

        return $queryBuilder;
    }

    /**
     * @param int $categoryId
     *
     * @return array
     */
    public function getSortedArticleResultsByCategoryId($categoryId)
    {
        $result       = array();
        $queryBuilder = $this->getArticleOrderedQueryObject($categoryId);
        if ($queryBuilder == null) {
            ShopgateLogger::getInstance()->log(
                "Null queryBuilder returned for category {$categoryId}", ShopgateLogger::LOGTYPE_DEBUG
            );

            return $result;
        }
        $queryBuilder->addSelect('SQL_CALC_FOUND_ROWS product.id as __product_id');
        $stmt = Shopware()->Db()->prepare($queryBuilder->getSQL());
        foreach ($queryBuilder->getParameters() as $key => $parameter) {
            $stmt->bindValue($key, $parameter);
        }
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $result[] = $row['__product_id'];
        }

        return $result;
    }
}
