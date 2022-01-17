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

namespace unit\Models;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Shopware\Models\Search\CustomSorting;

class ArticleTest extends TestCase
{
    /** @var \Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_Article */
    private $subjectUnderTest;

    /** @var \Shopware\Bundle\StoreFrontBundle\Service\CustomSortingServiceInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $customSortingServiceMock;

    /** @var \Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactoryInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $criteriaFactoryMock;

    /** @var \Shopware\Bundle\SearchBundleDBAL\QueryBuilderFactoryInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $queryBuilderFactoryMock;

    /** @var \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $shopContextMock;

    /** @var \Enlight_Controller_Request_Request | \PHPUnit_Framework_MockObject_MockObject */
    private $requestHttpMock;

    public function set_up()
    {
        $this->customSortingServiceMock =
            $this->getMockBuilder('Shopware\Bundle\StoreFrontBundle\Service\CustomSortingServiceInterface')
                ->setMethods(array('getSortingsOfCategories'))
                ->getMock();

        $this->queryBuilderFactoryMock = $this->getMockBuilder(
            'Shopware\Bundle\SearchBundleDBAL\QueryBuilderFactoryInterface'
        )
            ->setMethods(array('createQueryWithSorting'))
            ->getMock();

        $this->criteriaFactoryMock = $this->getMockBuilder(
            'Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactoryInterface'
        )
            ->setMethods(array('createListingCriteria'))
            ->getMock();

        $this->shopContextMock = $this->getMockBuilder('Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface')
            ->getMock();

        $this->requestHttpMock = $this->getMockBuilder('Enlight_Controller_Request_Request')
            ->setMethods(array('setParam'))
            ->getMock();

        $this->getMockBuilder('Shopware\Bundle\SearchBundle\Criteria')
            ->setMethods(array('addSorting'))->getMock();

        $this->subjectUnderTest = new \Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_Article(
            $this->customSortingServiceMock,
            $this->criteriaFactoryMock,
            $this->queryBuilderFactoryMock,
            $this->shopContextMock,
            $this->requestHttpMock
        );
    }

    public function testGetArticleOrderBySnippetFor()
    {
        $customSortings = array(
            '13' => array(
                $this->getCustomSorting(1, true, 1, array(array('direction' => 'DESC'))),
                $this->getCustomSorting(2, true, 2, array(array('direction' => 'ASC'))),
            ),
        );

        $this->customSortingServiceMock->expects($this->once())->method('getSortingsOfCategories')->willReturn(
            $customSortings
        );
        $this->requestHttpMock->expects($this->once())->method('setParam')->with('sSort', 1);

        $criteriaMock = $this->getMockBuilder('Shopware\Bundle\SearchBundle\Criteria')
            ->disableOriginalConstructor()
            ->getMock();
        $this->criteriaFactoryMock->expects($this->once())->method('createListingCriteria')->willReturn($criteriaMock);

        /** @var \PHPUnit_Framework_MockObject_MockObject $queryBuilderMock */
        $queryBuilderMock = $this->getMockBuilder('Shopware\Bundle\SearchBundleDBAL\QueryBuilder')
            ->setMethods(array('getQueryParts'))->getMock();
        $queryBuilderMock->expects($this->once())->method('getQueryParts')->willReturn(
            json_decode(
                '{"select":[],"from":[{"table":"s_articles","alias":"product"}],"join":{"product":[{"joinType":"inner","joinTable":"s_articles_details","joinAlias":"variant","joinCondition":"variant.id = product.main_detail_id\n                 AND variant.active = 1\n                 AND product.active = 1"},{"joinType":"left","joinTable":"s_articles_avoid_customergroups","joinAlias":"avoidCustomerGroup","joinCondition":"avoidCustomerGroup.articleID = product.id\n             AND avoidCustomerGroup.customerGroupId IN (:customerGroupIds38ba0f9d2195aa1ed719d712288c9fae)"}],"variant":[{"joinType":"inner","joinTable":"s_articles_attributes","joinAlias":"productAttribute","joinCondition":"productAttribute.articledetailsID = variant.id"}]},"set":[],"where":{},"groupBy":[],"having":null,"orderBy":["product.datum DESC","product.changetime DESC","product.id ASC"],"values":[]}',
                true
            )
        );

        $this->queryBuilderFactoryMock->expects($this->once())->method('createQueryWithSorting')->willReturn(
            $queryBuilderMock
        );

        $this->assertEquals(
            'Order By product.datum DESC, product.changetime DESC, product.id ASC',
            $this->subjectUnderTest->getArticleOrderBySQLSnippetFor(0)
        );
    }

    /**
     * @param int   $id
     * @param bool  $displayInCategories
     * @param int   $position
     * @param array $sortings
     *
     * @return \Shopware\Models\Search\CustomSorting
     */
    private function getCustomSorting($id, $displayInCategories, $position, array $sortings)
    {
        $customSortingMock = new CustomSorting($id, $displayInCategories, $position, $sortings);

        $customSortingMock->id                  = $id;
        $customSortingMock->displayInCategories = $displayInCategories;
        $customSortingMock->position            = $position;
        $customSortingMock->sortings            = $sortings;
        $customSortingMock->attributes          = array();

        return $customSortingMock;
    }
}
