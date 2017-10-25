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

use Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactory;

class ArticleLegacyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $expectedResult
     * @param int    $defaultListingSort
     *
     * @dataProvider provideDefaultListingSortCases
     */
    public function testGetArticleOrderBySnippetFor($expectedResult, $defaultListingSort)
    {
        $subjectUnderTest =
            new \Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Sort_ArticleLegacy($defaultListingSort);
        $this->assertEquals($expectedResult, $subjectUnderTest->getArticleOrderBySQLSnippetFor(0));
    }

    /**
     * @return array
     */
    public function provideDefaultListingSortCases()
    {
        return array(
            'sorting release date'   => array(
                'Order By product.datum DESC, product.changetime DESC, product.id DESC',
                StoreFrontCriteriaFactory::SORTING_RELEASE_DATE,
            ),
            'sorting search ranking' => array(
                'Order By searchTable.ranking DESC, product.id DESC',
                StoreFrontCriteriaFactory::SORTING_SEARCH_RANKING,
            ),
        );
    }
}
