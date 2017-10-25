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
class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Review_Xml extends Shopgate_Model_Catalog_Review
{
    /**
     * @var \Shopware\Models\Article\Vote $item
     */
    protected $item;

    /**
     * set id
     */
    public function setUid()
    {
        parent::setUid($this->item->getId());
    }

    /**
     * set product id for the review
     */
    public function setItemUid()
    {
        if ($this->item->getArticle()->getConfiguratorSet()) {
            $itemNumber = $this->item->getArticle()->getId();
        } else {
            $itemNumber = $this->item->getArticle()->getId() . "-" .
                $this->item->getArticle()->getMaindetail()->getNumber();
        }

        parent::setItemUid($itemNumber);
    }

    /**
     * set score for the review
     */
    public function setScore()
    {
        parent::setScore($this->_getScore());
    }

    /**
     * set username for the review
     */
    public function setReviewerName()
    {
        parent::setReviewerName($this->item->getName());
    }

    /**
     * set text for the review
     */
    public function setDate()
    {
        parent::setDate($this->item->getDatum()->format("Y-m-d"));
    }

    /**
     * set title for the review
     */
    public function setTitle()
    {
        parent::setTitle($this->item->getHeadline());
    }

    /**
     * set text for the review
     */
    public function setText()
    {
        parent::setText($this->item->getComment());
    }

    /**
     * @return float|number
     */
    protected function _getScore()
    {
        return $this->item->getPoints() * 2;
    }
}
