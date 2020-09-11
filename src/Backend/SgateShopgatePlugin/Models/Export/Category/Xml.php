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
class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Category_Xml extends Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Category
{
    /**
     * @var \Shopware\Models\Category\Category $item
     */
    protected $item;

    /**
     * set category sort order
     */
    public function setSortOrder()
    {
        parent::setSortOrder($this->maxCategoryPosition - $this->item->getPosition());
    }

    /**
     * generate data dom object
     *
     * @return $this
     */
    public function generateData()
    {
        foreach ($this->fireMethods as $method) {
            $this->{$method}($this->item);
        }

        return $this;
    }

    /**
     * set category id
     */
    public function setUid()
    {
        parent::setUid($this->item->getId());
    }

    /**
     * set category name
     */
    public function setName()
    {
        parent::setName($this->categoryContent['name']);
    }

    /**
     * set parent category id
     */
    public function setParentUid()
    {
        parent::setParentUid(
            $this->item->getParentId() != $this->rootCategoryId
                ? $this->item->getParentId()
                : null
        );
    }

    /**
     * category link in shop
     */
    public function setDeeplink()
    {
        parent::setDeeplink($this->getDeepLinkUrl($this->item));
    }

    /**
     * set category image
     */
    public function setImage()
    {
        $imageUrl = $this->getImageUrl($this->item);

        if ($imageUrl != null) {
            $imageItem = new Shopgate_Model_Media_Image();
            $imageItem->setUrl($this->getImageUrl($this->item));
            $imageItem->setTitle($this->getName());
            $imageItem->setAlt($this->getName());

            parent::setImage($imageItem);
        }
    }

    /**
     * set active state
     */
    public function setIsActive()
    {
        parent::setIsActive($this->item->getActive());
    }
}
