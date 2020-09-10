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
 * xml export for review
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 */
class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Category extends Shopgate_Model_Catalog_Category
{
    /**
     * @var int
     */
    protected $maxCategoryPosition = 0;

    /**
     * @var null
     */
    protected $rootCategoryId = null;

    /**
     * $var null
     */
    protected $categoryContent = null;

    /**
     * @param   int $categoryId
     *
     * @return $this
     */
    public function setRootCategoryId($categoryId)
    {
        $this->rootCategoryId = $categoryId;

        return $this;
    }

    /**
     * @param   int $maxPosition
     *
     * @return $this
     */
    public function setMaximumPosition($maxPosition)
    {
        $this->maxCategoryPosition = $maxPosition;

        return $this;
    }

    /**
     * @param array $categoryContent
     *
     * @return $this
     */
    public function setCategoryContent($categoryContent)
    {
        $this->categoryContent = $categoryContent;

        return $this;
    }

    /**
     * @param \Shopware\Models\Category\Category $category
     *
     * @return mixed
     */
    public function getImageUrl($category)
    {
        if ($category->getMedia()) {
            // It is possible to have inconsistent data that appears the getMedia() method to return an invalid media object, without really being existent
            // -> callin the getPath Method would throw an exception in that case
            try {
                if (version_compare(Shopware()->Config()->version, '5.6', '>=')) {
                    $mediaService = Shopware()->Container()->get('shopware_media.media_service');
                    $baseUrl = $mediaService->getUrl($category->getMedia()->getPath());
                } else {
                    $baseUrl = "http://";
                    $baseUrl .= Shopware()->Shop()->getHost();
                    $baseUrl .= Shopware()->Shop()->getBasePath();
                    $baseUrl .= "/";
                    $baseUrl .= $category->getMedia()->getPath();
                }
            } catch (Exception $e) {
                $baseUrl = null;
            }
        } else {
            $baseUrl = null;
        }

        return $baseUrl;
    }

    /**
     * @param \Shopware\Models\Category\Category $category
     *
     * @return mixed
     */
    public function getDeepLinkUrl($category)
    {
        $link = Shopware()->System()->sCONFIG['sBASEFILE'] . "?sViewport=cat&sCategory={$category->getId()}";
        $link = Shopware()->System()->sMODULES['sCore']->sRewriteLink($link);

        return $link;
    }

    /**
     * @param   \Shopware\Models\Category\Category $category
     *
     * @return  int
     */
    public function getOrderIndex($category)
    {
        if (method_exists($category, 'getLeft')) {
            $shopwareOrderIndex = $category->getLeft();
        } else {
            $shopwareOrderIndex = $category->getPosition();
        }

        return $this->maxCategoryPosition - $shopwareOrderIndex;
    }

    /**
     * @return array
     */
    public function getCategoryContent()
    {
        return $this->categoryContent;
    }
}
