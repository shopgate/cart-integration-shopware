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
class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Export_Product extends Shopgate_Model_Catalog_Product
{
    const AVAILABLE_TEXT_TEMPLATE = 'frontend/plugins/index/delivery_informations.tpl';
    const ORIGINAL                = 'original';

    /**
     * @var \Shopware\Models\Article\Detail $detail
     */
    protected $detail;

    /**
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export
     */
    protected $exportComponent = null;

    /**
     * @var Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config
     */
    protected $config = null;

    /**
     * @var null|string
     */
    protected $defaultCustomerGroupKey = null;

    /**
     * @var [string, mixed][] A list of key-value-pairs containing information about the attributes that should be
     *      attached to the description
     */
    protected static $attributesCache = array();

    /**
     * @var $locale Shopware\Models\Shop\Locale
     */
    protected $locale;

    /**
     * @var Shopware_Components_Translation
     */
    protected $translation;

    /**
     * @param Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export $exportComponent
     */
    public function __construct(Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export $exportComponent)
    {
        parent::__construct();
        $this->locale          =
            Shopware()->Models()->find("Shopware\Models\Shop\Shop", Shopware()->Shop()->getId())->getLocale();
        $this->exportComponent = $exportComponent;
        $this->translation     = new Shopware_Components_Translation();
    }

    /**
     * @param $detail
     *
     * @return $this
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * @param string $customerGroupKey
     */
    public function setDefaultCustomerGroupKey($customerGroupKey)
    {
        $this->defaultCustomerGroupKey = $customerGroupKey;
    }

    /**
     * @return null|string
     */
    public function getDefaultCustomerGroupKey()
    {
        return $this->defaultCustomerGroupKey;
    }

    /**
     * @param Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export $component
     */
    public function setExportComponent($component)
    {
        $this->exportComponent = $component;
    }

    /**
     * @return Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Export
     */
    public function getExportComponent()
    {
        return $this->exportComponent;
    }

    /**
     * returns the purchasesteps for a specific product variant
     *
     * @param \Shopware\Models\Article\Detail $detail
     *
     * @return int|null
     */
    public function getPurchaseSteps($detail)
    {
        $purchaseSteps = 1;
        if (is_object($detail) && $detail->getPurchaseSteps()) {
            $purchaseSteps = $detail->getPurchaseSteps();
        }

        return $purchaseSteps;
    }

    /**
     * @return Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config
     */
    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        }

        return $this->config;
    }

    /**
     * @param Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param \Shopware\Models\Article\Detail  $detail
     *
     * @return string
     */
    public function getAvailableText($detail)
    {
        $template = $this->config->assertMinimumVersion('4.2')
            ? Shopware()->Container()->get('Template')
            : Enlight_Application::Instance()->Bootstrap()->getResource('Template');

        $view           = new Enlight_View_Default($template);
        $sArticle = Shopware()->Modules()->Articles()->sGetProductByOrdernumber($detail->getNumber());
        $sArticle['shippingtime'] = $detail->getShippingTime();
        $view->sArticle = $sArticle;
        $availableText  = Shopware()->Template()->fetch(self::AVAILABLE_TEXT_TEMPLATE);
        $availableText  = strip_tags($availableText);
        $availableText  = html_entity_decode($availableText);
        $availableText  = trim($availableText);

        return $availableText;
    }

    /**
     * Takes a string price value with a comma or dot and creates a float value out of it before converting to a price
     * format.
     *
     * @param     $price
     * @param int $precision
     *
     * @return float
     */
    public function getFormattedPrice($price, $precision = 2)
    {
        return round(str_replace(",", ".", $price), $precision);
    }

    /**
     * @param \Shopware\Models\Article\Article $article
     * @param \Shopware\Models\Article\Detail  $details
     * @param                                  $isParent
     *
     * @return array
     */
    public function getImageUrls($article, $details, $isParent)
    {
        $result = array();
        if ($isParent) {
            // Parent articles need only one (the main) image, since it is not considered as a full item and is only there for display purposes
            // -> get the article image including the cached images (use cache in case the original has been deleted)
            $images = array();
            if ($this->getConfig()->assertMinimumVersion('4.2')) {
                $images = Shopware()->Modules()->Articles()->sGetArticlePictures(
                    $article->getId(),
                    true,
                    0,
                    null,
                    false,
                    false,
                    true
                );
            }
            if (!isset($images['src']) || empty($images['src'])) {
                $images = Shopware()->Modules()->Articles()->sGetArticlePictures($article->getId(), true, 0);
            }

            // Choose the best available picture in the list (original could also be removed)
            if (!empty($images['src'])) {
                $image = $this->getSingleArticleImage($images['src']);
            }

            if (!empty($image)) {
                $result[] = $image;
            }
        } else {
            // Load image URLs
            $articleImage  = Shopware()->Modules()->Articles()->sGetArticlePictures(
                $article->getId(),
                true,
                4,
                $details->getNumber()
            );
            $articleImages = Shopware()->Modules()->Articles()->sGetArticlePictures(
                $article->getId(),
                false,
                0,
                $details->getNumber()
            );

            // Take the "preview" image if available
            $image = '';
            if (!empty($articleImage['src'])) {
                $image = $this->getSingleArticleImage($articleImage['src']);
            }
            if (!empty($image)) {
                $result[] = $image;
            }

            // Append the rest
            foreach ($articleImages as $artImage) {
                $image = '';
                if (!empty($artImage['src'])) {
                    $image = $this->getSingleArticleImage($artImage['src']);
                }
                if (!empty($image)) {
                    $result[] = $image;
                }
            }
        }

        return $result;
    }

    /**
     * Takes a set of an image path including the cached images and takes the best available one (prefers the original
     * if available)
     *
     * @param array $imgSrcArray
     *
     * @return string
     */
    protected function getSingleArticleImage($imgSrcArray)
    {
        $image = '';
        if (!empty($imgSrcArray)) {
            // check if image url is hosted externally
            if ($this->isWebAddress($imgSrcArray[self::ORIGINAL])
                && !$this->isUrlInStoreDomain($imgSrcArray[self::ORIGINAL])
            ) {
                return $imgSrcArray[self::ORIGINAL];
            }

            if (!file_exists($this->getShopRootFS() . $this->getRelativeImagePath($imgSrcArray[self::ORIGINAL]))) {
                $images = array();
                foreach ($imgSrcArray as $image) {
                    // check for the highest resolution and availablitity of the image
                    if (preg_match('/(\d+)x(\d+)\./', $image, $size)
                        && file_exists($this->getShopRootFS() . $this->getRelativeImagePath($image))
                    ) {
                        $images[(int)$size[0] * (int)$size[1]] =
                            $this->getShopRootWS() . $this->getRelativeImagePath($image);
                    }
                }
                // sort from small to bigger images
                ksort($images);
                $image = end($images);
            } else {
                $image = $this->getShopRootWS() . $this->getRelativeImagePath($imgSrcArray[self::ORIGINAL]);
            }
        }

        return $image;
    }

    /**
     * Takes an absolute filesystem path or url (or alreay relative path) and converts it to a relative path by
     * removing the fs or ws root dir - it tries to get the relative image path (by using a more specific method) in
     * case if the web-path can't be resolved. Pre and post slashes are removed if there are any
     *
     * @param string $absolutePath
     *
     * @return string
     */
    protected function getRelativeImagePath($absolutePath)
    {
        $localImagePath = $this->getRelativePath($absolutePath);
        if (strpos($localImagePath, 'http://') === 0 || strpos($localImagePath, 'https://') === 0) {
            // web-url could not be resolved -> parse manually for the media folder
            if (($mediaPos = strpos($localImagePath, '/media/')) !== false) {
                // skip first "/"-Symbol
                $localImagePath = substr($localImagePath, $mediaPos + 1);
                // remove pre- and post-slashes
                $localImagePath = trim($localImagePath, DS);
            }
        }

        return $localImagePath;
    }

    /**
     * Takes an absolute filesystem path or url (or alreay relative path) and converts it to a relative path by
     * removing the fs or ws root dir. Pre and post slashes are removed if there are any
     *
     * @param string $absolutePath
     *
     * @return string
     */
    protected function getRelativePath($absolutePath)
    {
        if (strpos($absolutePath, 'http://') === 0 || strpos($absolutePath, 'https://') === 0) {
            // URL given
            $rootDir = $this->getShopRootWS();
        } elseif (strpos($absolutePath, trim($rootDir = $this->getShopRootFS(), DS)) === 0) {
            // fs root matched
            // $rootDir has already been assigned, since getShopRootFS has already been called and directly saved to $rootDir while testing for match
        } else {
            // no match, assume its already relative
            $rootDir = $absolutePath;
        }

        return trim(str_replace($rootDir, '', $absolutePath), DS);
    }

    /**
     * Builds a filesystem path to the shop root directory and returns it
     *
     * @return string
     */
    protected function getShopRootFS()
    {
        return Shopware()->DocPath();
    }

    /**
     * Builds a webserver url to the shop root directory and returns it.
     *
     * Note: Not using Shopware()->Shop()->getBaseUrl() because it's not available in older versions.
     *
     * @return string
     */
    protected function getShopRootWS()
    {
        return (
            "http://" .

            // strip "http://" and "https://"; trim slashes and append a single one
            trim(str_replace(array('http://', 'https://'), '', Shopware()->Shop()->getHost()), '/') . '/' .

            // append base path of the shop with slashes trimmed and a single one appended
            trim(Shopware()->Shop()->getBasePath(), '/') . '/'
        );
    }

    /**
     * determines if url is a web address
     *
     * @param string $address
     * @return bool
     */
    protected function isWebAddress($address)
    {
        return preg_match('/^https?:\/\/.*/i', $address) !== false;
    }

    /**
     * determines if url is in store's domain
     *
     * @param string $address
     * @return bool
     */
    protected function isUrlInStoreDomain($address)
    {
        return stripos($address, $this->getShopRootWS()) !== false;
    }

    /**
     * Prepare description, strip new lines
     *
     * @param $articleData
     *
     * @return mixed
     */
    public function prepareDescription($articleData)
    {
        switch ($this->getConfig()->getExportProductDescription()) {
            case Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::EXPORT_PRODUCT_DESCTIPTION_SHORT_DESC:
                $description = $articleData['description'];
                break;
            case Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::EXPORT_PRODUCT_DESCTIPTION_DESC_AND_SHORT_DESC:
                $description = $articleData['description_long'];
                $description .= '<br /><br />';
                $description .= $articleData['description'];
                break;
            case Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::EXPORT_PRODUCT_DESCTIPTION_SHORT_DESC_AND_DESC:
                $description = $articleData['description'];
                $description .= '<br /><br />';
                $description .= $articleData['description_long'];
                break;
            default:
                $description = $articleData['description_long'];
        }

        $description = $this->getCustomFieldsAsDescription($description);
        $description = $this->addDownloadsToDescription($articleData['sDownloads'], $description);

        // remove all newlines and carriage returns
        if (!empty($description)) {
            $description = str_replace(array("\r", "\n"), '', $description);
        }

        return $description;
    }

    /**
     * Add download links to description
     *
     * @param array  $downloads
     * @param string $descriptionWithoutLinks
     *
     * @return string
     */
    public function addDownloadsToDescription($downloads, $descriptionWithoutLinks)
    {
        if (empty($downloads)) {
            return $descriptionWithoutLinks;
        }

        switch ($this->getConfig()->getExportProductDownloads()) {
            case Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::EXPORT_PRODUCT_DOWNLOADS_NO:
                return $descriptionWithoutLinks;
            case Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::EXPORT_PRODUCT_DOWNLOADS_ABOVE_DESCRIPTION:
                $description = $this->exportComponent->getTemplateText("DetailDescriptionHeaderDownloads");
                $description .= $this->createDescriptionDownloadLinks($downloads);
                $description .= '<br /><br />';
                $description .= $descriptionWithoutLinks;

                return $description;
            case Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::EXPORT_PRODUCT_DOWNLOADS_BELOW_DESCRIPTION:
                $description = $descriptionWithoutLinks;
                $description .= '<br /><br />';
                $description .= $this->exportComponent->getTemplateText("DetailDescriptionHeaderDownloads");
                $description .= $this->createDescriptionDownloadLinks($downloads);

                return $description;
            default:
                return $descriptionWithoutLinks;
        }
    }

    /**
     * Prepare download links for description
     *
     * @param array $downloads
     *
     * @return array
     */
    public function createDescriptionDownloadLinks($downloads)
    {
        $links = '';
        foreach ($downloads as $download) {
            $links .= '<br /><br />';
            $links .=
                '<a href="' .
                $download['filename'] .
                '">> ' .
                $this->exportComponent->getTemplateText("DetailDescriptionLinkDownload") .
                " " .
                $download['description'] . '</a>';
        }

        return $links;
    }

    /**
     * @param string $description
     *
     * @return string
     */
    protected function getCustomFieldsAsDescription($description)
    {
        $attributesAsDescription = array_filter($this->config->getExportAttributesAsDescription());
        if (empty($attributesAsDescription)) {
            return $description;
        }

        $elements  = array_intersect($this->exportComponent->getArticleElements(), $attributesAsDescription);
        $customFields = array();
        foreach ($elements as $elementIndex => $elementName) {
            if (!empty($this->articleData[$elementIndex])) {
                $customField = $this->articleData[$elementIndex];
            }

            $getterName = 'get' . $elementIndex;
            if (!empty($this->detail) && method_exists($this->detail->getAttribute(), $getterName)) {
                $detailAttr = $this->detail->getAttribute()->{$getterName}();
                if (!empty($detailAttr)) {
                    $customField = $detailAttr;
                }
            }

            if (!empty($customField)) {
                $customFields[] = "<h4>{$elementName}</h4>\n{$customField}\n\n";
            }
            unset($customField);
        }

        return $description . (
            empty($customFields)
                ? ''
                : '<br />' . implode('<br /><br />', $customFields) . '<br /><br />'
            );
    }

    /**
     * Prepare internal order infos for article/detail
     *
     * @param \Shopware\Models\Article\Article $article
     * @param \Shopware\Models\Article\Detail  $detail
     *
     * @return array
     */
    public function prepareInternalOrderInfo($article, $detail)
    {
        $infos                      = array();
        $infos['item_id']           = $article->getId(); // deprecated
        $infos['article_id']        = $article->getId();
        $infos['article_detail_id'] = $detail->getId();
        $infos['tax_id']            = $article->getTax()->getId();
        $infos['purchasesteps']     = $this->getPurchaseSteps($detail)
            ? $this->getPurchaseSteps($detail)
            : '1';

        return $infos;
    }

    /**
     * @param \Shopware\Models\Article\Detail $detail
     *
     * @return mixed
     */
    public function prepareWeight($detail)
    {
        return $detail->getWeight() * 1000;
    }

    /**
     * Set category path
     *
     * @param \Shopware\Models\Article\Article $article
     *
     * @return array
     */
    public function getCategories($article)
    {
        $catIds = array();
        foreach ($article->getCategories() as $cat) {
            /* @var $cat \Shopware\Models\Category\Category */
            if (
                $cat->getBlog()
                || $this->config->assertMinimumVersion('5.1')
                && !is_null($cat->getStream())
            ) {
                continue;
            }
            if ($this->exportComponent->checkCategory($cat->getId())) {
                $index                 =
                    $this->exportComponent->getArticleOrderIndex($article->getId(), $cat->getId());
                $catIds[$cat->getId()] = array(
                    'id'         => $cat->getId(),
                    'sort_order' => $index,
                );
            }
            foreach ($this->getParentCategoryIds($cat) as $parentId) {
                if ($this->exportComponent->checkCategory($parentId)) {
                    $index             =
                        $this->exportComponent->getArticleOrderIndex($article->getId(), $parentId);
                    $catIds[$parentId] = array(
                        'id'         => $parentId,
                        'sort_order' => $index,
                    );
                }
            }
        }

        $catIds = $this->config->assertMinimumVersion('5.1')
            ? $catIds + $this->exportComponent->getStreamCategories($article->getId())
            : $catIds;

        return $catIds;
    }

    /**
     * @param \Shopware\Models\Category\Category $cat
     *
     * @return array
     */
    public function getParentCategoryIds(\Shopware\Models\Category\Category $cat)
    {
        $result = array();
        if ($cat->getParentId()) {
            $result[] = $cat->getParentId();
            $result   = array_merge($result, $this->getParentCategoryIds($cat->getParent()));
        }

        return $result;
    }

    /**
     * Prepare item properties prior to xml/csv export
     *
     * @param                                 $article
     * @param \Shopware\Models\Article\Detail $detail
     *
     * @return array
     */
    public function prepareProperties($article, $detail)
    {
        $properties = array();

        if ($detail->getSupplierNumber()) {
            $properties['Herstellernummer'][] = $detail->getSupplierNumber();
        }

        if ($detail->getPurchaseUnit() > 0
            && $detail->getUnit() != null
            && $detail->getUnit()->getId()
        ) {
            $steps                  = str_replace(".", ",", round($detail->getPurchaseUnit(), 2));
            $properties['Inhalt'][] = $steps . ' ' . $detail->getUnit()->getName();
        }

        if ($this->getPurchaseSteps($detail)) {
            $steps = round($this->getPurchaseSteps($detail), 0);
            if ($steps > 1) {
                $properties['Menge'][] = $steps . 'er Packung';
            }
        }

        /* @var $attribute \Shopware\Models\Attribute\Article */
        $attribute = $detail->getAttribute();
        $elements  = $this->exportComponent->getArticleElements();
        if ($attribute) {
            foreach ($elements as $attr => $label) {
                if (!in_array($label, $this->getConfig()->getExportAttributes())
                    || !method_exists($attribute, 'get' . $attr)
                ) {
                    continue;
                }

                $attr = $attribute->{"get$attr"}();
                if (!empty($attr)) {
                    $properties[$label][] = $attr;
                }
            }
        }

        $sizeUnit = $this->config->getExportDimensionUnit();
        if ($sizeUnit != -1) {
            if ((float)$detail->getWidth()) {
                $properties['Breite'][] = (float)$detail->getWidth() . " $sizeUnit";
            }
            if ((float)$detail->getHeight()) {
                $properties['Höhe'][] = (float)$detail->getHeight() . " $sizeUnit";
            }
            if ((float)$detail->getLen()) {
                $properties['Länge'][] = (float)$detail->getLen() . " $sizeUnit";
            }
        }

        foreach ($article['sProperties'] as $property) {
            $optionName = $this->translation->read($this->locale->getId(), 'propertyoption', $property['optionID']);
            $optionName = empty($optionName['optionName'])
                ? $property['name']
                : $optionName['optionName'];

            $optionValue = $this->translation->read($this->locale->getId(), 'propertyvalue', $property['valueID']);
            $optionValue = empty($optionValue['optionValue'])
                ? $property['value']
                : $optionValue['optionValue'];

            if (!empty($optionValue)) {
                $properties[$optionName][] = $optionValue;
            }
        }

        return $properties;
    }

    /**
     * Get all relateted item for given article
     *
     * @param \Shopware\Models\Article\Article $article
     *
     * @return array
     */
    public function getRelatedItems($article)
    {
        $relatedArticles = array();

        /* @var $art \Shopware\Models\Article\Article */
        foreach ($article->getRelated()->getIterator() as $art) {
            if ($art->getConfiguratorSet() && !$this->getIsChild()) {
                $itemNumber = $art->getId();
            } else {
                if (!is_object($art->getMainDetail())) {
                    continue;
                } else {
                    $itemNumber = $art->getId() . "-" . $art->getMaindetail()->getNumber();
                }
            }
            $relatedArticles[] = $itemNumber;
        }

        return $relatedArticles;
    }

    /**
     * Get all similar item for given article
     *
     * @param \Shopware\Models\Article\Article $article
     *
     * @return array
     */
    public function getSimilarItems($article)
    {
        $similarArticles = array();

        /* @var $art \Shopware\Models\Article\Article */
        foreach ($article->getSimilar()->getIterator() as $art) {
            if ($art->getConfiguratorSet() && !$this->getIsChild()) {
                $itemNumber = $art->getId();
            } else {
                if (!is_object($art->getMainDetail())) {
                    continue;
                } else {
                    $itemNumber = $art->getId() . "-" . $art->getMaindetail()->getNumber();
                }
            }

            $similarArticles[] = $itemNumber;
        }

        return $similarArticles;
    }

    /**
     * Prepares base price
     *
     * @param float                           $price The price the product is being sold for.
     * @param \Shopware\Models\Article\Detail $detail
     *
     * @return string
     */
    public function prepareBasePrice($price, $detail)
    {
        $currencySymbol = Shopware()->System()->sCurrency["symbol"];
        $basePrice      = '';

        if ($detail->getPurchaseUnit() > 0
            && $detail->getUnit() != null
            && $detail->getUnit()->getId()
        ) {
            // remove trailing zeros to the right of the decimal point
            $referenceUnit = (string)(float)$detail->getReferenceUnit();
            $unit          = $detail->getUnit()->getName();

            $amount = ($price / $detail->getPurchaseUnit()) * $detail->getReferenceUnit();
            $amount = Shopware()->Modules()->Articles()->sFormatPrice($amount);

            $basePrice = $amount;

            if (!empty(Shopware()->System()->sCurrency["symbol_position"])
                && Shopware()->System()->sCurrency["symbol_position"] == 32
            ) {
                // Symbol on left side
                $basePrice = $currencySymbol . " " . $basePrice;
            } else {
                // Symbol on right side
                $basePrice = $basePrice . " " . $currencySymbol;
            }

            $basePrice .= " / {$referenceUnit} {$unit}";
        } elseif ($this->getPurchaseSteps($detail) > 1) {
            $amount    = Shopware()->Modules()->Articles()->sFormatPrice($price);
            $basePrice = "{$amount} {$currencySymbol} pro St&uuml;ck";
        }

        return $basePrice;
    }
}
