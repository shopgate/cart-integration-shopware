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
 * xml export for articles
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 */
class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product_Xml extends Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product
{
    /** @var Shopware\Models\Article\Detail[] */
    private $childCache;

    /** @var Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation */
    private $translationModel;

    /** @var Shopware\Models\Article\Article $article */
    protected $article;

    /** @var array */
    protected $articleData = array();

    /** @var Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product_Xml $parent */
    protected $parent = false;

    /**@var bool */
    public $validChild = true;

    /**
     * @param Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export  $exportComponent
     * @param Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation $translationModel
     */
    public function __construct(
        Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export $exportComponent,
        Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation $translationModel
    ) {
        parent::__construct($exportComponent);

        $this->translationModel = $translationModel;
    }

    /**
     * @param $article
     *
     * @return $this
     */
    public function setArticle($article)
    {
        $this->article = $article;

        return $this;
    }

    /**
     * @param $data
     *
     * @return $this
     */
    public function setArticleData($data)
    {
        $this->articleData = $data;

        return $this;
    }

    /**
     * get parent
     *
     * @return false|$this
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * set parent
     *
     * @return $this
     */
    public function setParent($value)
    {
        $this->parent = $value;

        return $this;
    }

    public function setLastUpdate()
    {
        parent::setLastUpdate($this->article->getChanged()->format("c"));
    }

    public function setUid()
    {
        $uid = (!$this->getIsChild())
            ? $this->article->getId()
            : $this->article->getId() . "-" . $this->detail->getNumber();

        parent::setUid($uid);
    }

    public function setName()
    {
        $articleDetail = $this->detail;
        $purchaseSteps = $this->getPurchaseSteps($articleDetail);
        if (!empty($purchaseSteps) && $purchaseSteps > 1) {
            parent::setName($this->article->getName() . ' ' . $purchaseSteps . 'er Packung');
        } else {
            parent::setName($this->article->getName());
        }
    }

    public function setTaxPercent()
    {
        parent::setTaxPercent($this->article->getTax()->getTax());
    }

    public function setTaxClass()
    {
        parent::setTaxClass('tax_' . $this->article->getTax()->getTax() . '_' . $this->article->getTax()->getId());
    }

    public function setCurrency()
    {
        parent::setCurrency(Shopware()->System()->sCurrency['currency']);
    }

    public function setDescription()
    {
        parent::setDescription($this->prepareDescription($this->articleData));
    }

    public function setDeeplink()
    {
        parent::setDeeplink($this->articleData['linkDetailsRewrited']);
    }

    public function setInternalOrderInfo()
    {
        $infos = $this->prepareInternalOrderInfo($this->article, $this->detail);
        parent::setInternalOrderInfo($this->jsonEncode($infos));
    }

    public function setWeight()
    {
        parent::setWeight($this->prepareWeight($this->detail));
    }

    public function setPrice()
    {
        $priceModel = new Shopgate_Model_Catalog_Price();

        $price         = $this->getFormattedPrice($this->articleData['price']);
        $pseudoPrice   = $this->getFormattedPrice($this->articleData['pseudoprice']);
        $articleDetail = $this->detail;

        // child items may have varying prices
        if ($this->getIsChild()) {
            $childPriceModel = $this->exportComponent->getPriceModel(
                $this->detail->getId(),
                $this->getDefaultCustomerGroupKey()
            );

            if (empty($childPriceModel)) {
                $childPriceModel = $this->exportComponent->getPriceModel(
                    $this->detail->getId(),
                    'EK'
                );
            }

            if (!empty($childPriceModel)) {
                $articleDetail = $childPriceModel->getDetail();
                $taxFactor     = 1 + $this->articleData['tax'] / 100;
                $price         = $childPriceModel->getPrice() * $taxFactor;
                $pseudoPrice   = $this->getFormattedPrice(
                    $childPriceModel->getPseudoPrice() * $taxFactor
                );
            } else {
                $price = 0;
            }
        }

        $purchaseSteps = $this->getPurchaseSteps($articleDetail);
        $price         = $this->getFormattedPrice($price);

        if (!empty($purchaseSteps)) {
            $price       *= $purchaseSteps;
            $pseudoPrice *= $purchaseSteps;
        }

        $pseudoPrice = max($pseudoPrice, $price);
        $priceModel->setSalePrice($price);
        $priceModel->setPrice($pseudoPrice);

        if ($this->articleData['tax']) {
            $priceModel->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS);
        } else {
            $priceModel->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET);
        }

        if ($priceGroup = $this->article->getPriceGroup()) {
            foreach ($priceGroup->getDiscounts() as $discount) {
                $tierPrice = new Shopgate_Model_Catalog_TierPrice();
                /** @var \Shopware\Models\Price\Discount $discount */
                $tierPrice->setCustomerGroupUid($discount->getCustomerGroupId());
                $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_PERCENT);
                $tierPrice->setReduction($discount->getDiscount());
                $tierPrice->setFromQuantity($discount->getStart());
                $priceModel->addTierPriceGroup($tierPrice);
            }
        }

        $tierPrices = Shopware()->Db()->fetchAll(
            "Select * from s_articles_prices WHERE articledetailsID = ?",
            $this->detail->getId()
        );

        foreach ($tierPrices as $blockPrice) {
            if (!$blockPrice['price'] > 0) {
                continue;
            }

            $tierPrice      = new Shopgate_Model_Catalog_TierPrice();
            $blockPriceFrom = $blockPrice['from'];
            $blockPriceTo   = $blockPrice['to'];
            $taxFactor      = (100 + $this->getFormattedPrice($this->articleData['tax'])) / 100;
            $tmpBlockPrice  = $this->getFormattedPrice($blockPrice['price'], 4);

            if (!empty($purchaseSteps)) {
                // be aware this only works if everything was set up properly from the merchant. e.g.: Step: 3
                $blockPriceFrom = floor(($blockPrice['from'] - 1) / $purchaseSteps) + 1;

                if ($blockPrice['to'] !== 'beliebig') {
                    $blockPriceTo = floor($blockPrice['to'] / $purchaseSteps);
                    if ($blockPriceTo < $blockPriceFrom) {
                        // special case if tier price class is too low (stack: 4, tier price quantity is between 1-3)
                        continue;
                    }
                }

                $tmpBlockPrice *= $purchaseSteps;
            }

            if ($blockPrice['to'] !== 'beliebig') {
                $tierPrice->setToQuantity($blockPriceTo);
            }

            $tierPrice->setFromQuantity($blockPriceFrom);
            $reduction = $this->getFormattedPrice($price) - $this->getFormattedPrice(($tmpBlockPrice * $taxFactor));

            $tierPrice->setReduction($reduction);
            $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
            if ($this->getDefaultCustomerGroupKey() != $blockPrice['pricegroup']) {
                $groupUid = $this->exportComponent->getCustomerGroupIdByKey($blockPrice['pricegroup']);
                if ($groupUid == null) {
                    continue;
                }
                $tierPrice->setCustomerGroupUid($groupUid);
            }

            if ($reduction > 0) {
                $priceModel->addTierPriceGroup($tierPrice);
            } elseif ($reduction <= 0 && $this->getIsChild() && empty($groupUid)) {
                // force empty tier_prices node for children without own tier_prices
                $parentTierPrices = $this->getParent()->getPrice()->getTierPricesGroup();
                if (!empty($parentTierPrices)) {
                    $tierPrice->setReduction(0);
                    $priceModel->addTierPriceGroup($tierPrice);
                }
            }
        }

        $customerGroups = Shopware()->Db()->fetchAll("SELECT * FROM s_core_customergroups WHERE mode = 1");
        foreach ($customerGroups as $group) {
            if ($group['discount'] > 0) {
                $tierPrice = new Shopgate_Model_Catalog_TierPrice();
                $tierPrice->setFromQuantity(1);
                $tierPrice->setCustomerGroupUid($group['id']);
                $tierPrice->setReduction($group['discount']);
                $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_PERCENT);
                $priceModel->addTierPriceGroup($tierPrice);
            }
        }

        if (!empty($purchaseSteps)) {
            $price /= $purchaseSteps;
        }

        $priceModel->setBasePrice(
            $this->prepareBasePrice(
                $this->getFormattedPrice($price),
                $articleDetail
            )
        );

        parent::setPrice($priceModel);
    }

    public function setShipping()
    {
        $shipping = new Shopgate_Model_Catalog_Shipping();
        $shipping->setAdditionalCostsPerUnit(0.0);
        $shipping->setCostsPerOrder(0.0);
        $shipping->setIsFree($this->detail->getShippingFree());

        parent::setShipping($shipping);
    }

    public function setManufacturer()
    {
        if ($this->article->getSupplier()) {
            $manufacturer = new Shopgate_Model_Catalog_Manufacturer();
            $manufacturer->setUid($this->article->getSupplier()->getId());
            $manufacturer->setTitle($this->article->getSupplier()->getName());
            $manufacturer->setItemNumber(false);
            parent::setManufacturer($manufacturer);
        }
    }

    public function setVisibility()
    {
        $level = ($this->getIsChild())
            ? Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_NOT_VISIBLE
            : Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG_AND_SEARCH;

        $visibility = new Shopgate_Model_Catalog_Visibility();
        $visibility->setLevel($level);
        $visibility->setMarketplace(true);

        parent::setVisibility($visibility);
    }

    public function setStock()
    {
        $stockQty    = $this->detail->getInStock();
        $maxQuantity = $this->detail->getMaxPurchase();
        $minQuantity = $this->detail->getMinPurchase();

        if ($this->getPurchaseSteps($this->detail)) {
            $stockQty    = floor($stockQty / $this->getPurchaseSteps($this->detail));
            $maxQuantity = floor($maxQuantity / $this->getPurchaseSteps($this->detail));
            $minQuantity = ceil($minQuantity / $this->getPurchaseSteps($this->detail));
        }

        $stockModel = new Shopgate_Model_Catalog_Stock();
        $useStock   = $this->article->getLastStock()
            ? true
            : false;
        $stockModel->setAvailabilityText($this->getAvailableText($this->detail));
        $stockModel->setStockQuantity($stockQty);
        $stockModel->setMaximumOrderQuantity($maxQuantity);
        $stockModel->setMinimumOrderQuantity($minQuantity);

        if ($useStock) {
            $isSalable = $stockQty > 0
                ? 1
                : 0;

            $stockModel->setIsSaleable($isSalable);
            $stockModel->setUseStock(1);
        } else {
            $stockModel->setIsSaleable(1);
            $stockModel->setUseStock(0);
        }

        parent::setStock($stockModel);
    }

    public function setImages()
    {
        $result   = array();
        $children = $this->loadChildren();
        $isParent = (!$this->getIsChild() && !empty($children));
        $images   = $this->getImageUrls($this->article, $this->detail, $isParent);
        if (!empty($images)) {
            $i = 0;
            foreach ($images as $image) {
                $imagesItemObject = new Shopgate_Model_Media_Image();
                $imagesItemObject->setUrl($image);
                $imagesItemObject->setTitle($this->article->getName());
                $imagesItemObject->setAlt($this->article->getName());
                $imagesItemObject->setSortOrder($i++);
                $result[] = $imagesItemObject;
            }
        }
        parent::setImages($result);
    }

    public function setCategoryPaths()
    {
        $result           = array();
        $linkedCategories = $this->getCategories($this->article);

        foreach ($linkedCategories as $category) {
            $sortOrder = $category['sort_order']
                ? $category['sort_order']
                : '';

            $categoryItemObject = new Shopgate_Model_Catalog_CategoryPath();
            $categoryItemObject->setUid($category['id']);
            $categoryItemObject->setSortOrder($sortOrder);

            $result[] = $categoryItemObject;
        }

        parent::setCategoryPaths($result);
    }

    public function setProperties()
    {
        $properties = $this->prepareProperties($this->articleData, $this->detail);
        $result     = array();
        foreach ($properties as $label => $value) {
            if (!empty($value)) {
                $value              = implode(', ', $value);
                $propertyItemObject = new Shopgate_Model_Catalog_Property();
                $propertyItemObject->setUid(bin2hex($label));
                $propertyItemObject->setLabel($label);
                $propertyItemObject->setValue($value);
                $result[] = $propertyItemObject;
            }
        }

        parent::setProperties($result);
    }

    public function setIdentifiers()
    {
        $result = array();

        $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
        $identifierItemObject->setType('SKU');
        $identifierItemObject->setValue($this->detail->getNumber());
        $result[] = $identifierItemObject;

        $identifierItemObject = new Shopgate_Model_Catalog_Identifier();
        $identifierItemObject->setType('EAN');
        $identifierItemObject->setValue($this->detail->getEan());
        $result[] = $identifierItemObject;
        parent::setIdentifiers($result);
    }

    public function setTags()
    {
        $result = array();
        $tags   = explode(',', $this->article->getKeywords());

        foreach ($tags as $tag) {
            if (!ctype_space($tag) && !empty($tag)) {
                $tagItemObject = new Shopgate_Model_Catalog_Tag();
                $tagItemObject->setValue(trim($tag));
                $result[] = $tagItemObject;
            }
        }

        parent::setTags($result);
    }

    public function setRelations()
    {
        $result = array();

        $relatedIds = array_merge(
            $this->getRelatedItems($this->article),
            $this->getSimilarItems($this->article)
        );
        if (!empty($relatedIds)) {
            $relatedRelation = new Shopgate_Model_Catalog_Relation();
            $relatedRelation->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL);
            $relatedRelation->setValues($relatedIds);
            $result[] = $relatedRelation;
        }

        parent::setRelations($result);
    }

    public function setAttributeGroups()
    {
        if ($this->hasChildren() && $this->article->getConfiguratorSet()) {
            $i      = 1;
            $result = array();

            /* @var $group Shopware\Models\Article\Configurator\Group */
            foreach ($this->article->getConfiguratorSet()->getGroups() as $group) {
                $attributeItem = new Shopgate_Model_Catalog_AttributeGroup();
                $attributeItem->setUid($i);
                $attributeItem->setLabel(
                    $this->translationModel->translate(
                        Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation::TRANSLATION_KEY_CONFIGURATION_GROUP,
                        $group->getId(),
                        $group->getName()
                    )
                );
                $result[] = $attributeItem;
                $i++;
            }

            parent::setAttributeGroups($result);
        }
    }

    /**
     * @return bool
     */
    private function hasChildren()
    {
        foreach ($this->loadChildren() as $childProduct) {
            if ($childProduct->getConfiguratorOptions()->count()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Shopware\Models\Article\Detail[]
     */
    private function loadChildren()
    {
        if (($this->article->getDetails()->count() < 1) || !$this->article->getConfiguratorSet()) {
            return array();
        }

        if ($this->childCache === null) {
            // get all article details and create an option count
            $query  = Shopware()->Db()->query(
                '
                SELECT sad.id, count(*) as option_count
                FROM `s_articles`
                JOIN `s_article_configurator_set_group_relations` as sacsgr ON sacsgr.set_id = s_articles.configurator_set_id
                JOIN `s_article_configurator_options` as saco ON saco.group_id = sacsgr.group_id
                JOIN `s_article_configurator_option_relations` AS sacor ON sacor.option_id = saco.id
                JOIN `s_article_configurator_set_option_relations` as sacsor ON sacsor.set_id = s_articles.configurator_set_id AND sacsor.option_id = saco.id
                JOIN `s_articles_details` as sad ON sad.id = sacor.article_id AND sad.active = 1
                WHERE s_articles.id = ? AND sad.articleId = ?
                GROUP BY sad.id
            ',
                array($this->article->getId(), $this->article->getId())
            );
            $result = $query->fetchAll();

            $detailIds                      = array();
            $maximumOptionsPerArticleDetail = 0;
            foreach ($result as $detailId) {
                $detailIds[$detailId['option_count']][] = $detailId['id'];
                if ($detailId['option_count'] > $maximumOptionsPerArticleDetail) {
                    $maximumOptionsPerArticleDetail = $detailId['option_count'];
                }
            }

            $this->childCache = array();
            if (!empty($detailIds[$maximumOptionsPerArticleDetail])) {
                // only valid variants should be exported. All exported variants need to have at least
                // $numberOfOptionsPerArticleDetail options otherwise there is something wrong
                $builder = Shopware()->Models()->createQueryBuilder();
                $builder->select(array('details'))
                    ->from('Shopware\Models\Article\Detail', 'details')
                    ->where('details.id IN (:detailsIds)')
                    ->setParameter('detailsIds', $detailIds[$maximumOptionsPerArticleDetail]);
                $this->childCache = $builder->getQuery()->getResult();
            }
        }

        return $this->childCache;
    }

    /**
     * set variants if article is parent
     */
    public function setChildren()
    {
        $children = array();

        if (!$this->getIsChild()) {
            $childProducts = $this->loadChildren();

            foreach ($childProducts as $childProduct) {
                if ($childProduct->getConfiguratorOptions()->count()) {
                    $child = new Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product_Xml(
                        $this->getExportComponent(),
                        $this->translationModel
                    );
                    $child->setDefaultCustomerGroupKey($this->getDefaultCustomerGroupKey());
                    $child->setDetail($childProduct);
                    $child->setArticle($this->article);
                    $child->setArticleData($this->articleData);
                    $child->setParent($this);
                    $child->setIsChild(true);
                    $child->setAttributes($this->article);
                    $child->generateData();

                    if ($child->validChild) {
                        $children[] = $child;
                    }
                }
            }
        }

        parent::setChildren($children);
    }

    /**
     * @param Shopware\Models\Article\Article $parent
     */
    public function setAttributes($parent)
    {
        $result = array();

        if ($this->article->getConfiguratorSet()) {
            $map = array();
            $i   = 1;

            if ($this->article->getConfiguratorSet()->getGroups()->isEmpty()) {
                $this->validChild = false;

                return;
            }

            foreach ($this->article->getConfiguratorSet()->getGroups() as $group) {
                $map[$group->getId()] = $i;
                $i++;
            }

            /* @var $option \Shopware\Models\Article\Configurator\Option */
            foreach ($this->detail->getConfiguratorOptions() as $option) {
                $group         = $option->getGroup();
                if (empty($map[$group->getId()])) {
                    continue;
                }
                $itemAttribute = new Shopgate_Model_Catalog_Attribute();
                $itemAttribute->setGroupUid($map[$group->getId()]);
                $itemAttribute->setLabel(
                    $this->translationModel->translate(
                        Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation::TRANSLATION_KEY_CONFIGURATION_OPTION,
                        $option->getId(),
                        $option->getName()
                    )
                );

                $result[] = $itemAttribute;
            }
        }

        parent::setAttributes($result);
    }

    /**
     * @return string|void
     */
    public function setDisplayType()
    {
        if ($this->article->getConfiguratorSet()
            && !$this->getIsChild()
        ) {
            parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_SELECT);
        } else {
            parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_SIMPLE);
        }
    }
}
