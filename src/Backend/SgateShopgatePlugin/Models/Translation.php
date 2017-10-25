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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Translation
{
    const TRANSLATION_KEY_CONFIGURATION_OPTION = 'configuratoroption';
    const TRANSLATION_KEY_CONFIGURATION_GROUP  = 'configuratorgroup';

    /** @var int */
    private $shopId;

    /** @var int */
    private $fallbackShopId;

    /** @var Shopware_Components_Translation */
    private $shopwareTranslateComponent;

    /**
     * @param Shopware_Components_Translation $shopwareTranslateComponent
     * @param int                             $shopId
     * @param int                             $fallbackShopId
     */
    public function __construct(
        Shopware_Components_Translation $shopwareTranslateComponent,
        $shopId,
        $fallbackShopId
    ) {
        $this->shopwareTranslateComponent = $shopwareTranslateComponent;
        $this->shopId                     = $shopId;
        $this->fallbackShopId             = $fallbackShopId;
    }

    /**
     * @param string $objectType
     * @param string $objectKey
     * @param string $originalString
     *
     * @return string
     */
    public function translate($objectType, $objectKey, $originalString)
    {
        $translatedString = $this->getTranslation($this->shopId, $objectType, $objectKey);

        if (empty($translatedString) && $this->fallbackShopId) {
            $translatedString = $this->getTranslation($this->fallbackShopId, $objectType, $objectKey);
        }

        return !empty($translatedString)
            ? $translatedString
            : $originalString;
    }

    /**
     * @param int    $shopId
     * @param string $objectType
     * @param string $objectKey
     *
     * @return null
     */
    private function getTranslation($shopId, $objectType, $objectKey)
    {
        $translation = $this->shopwareTranslateComponent->read($shopId, $objectType, $objectKey);

        return (isset($translation['name']))
            ? $translation['name']
            : null;
    }
}
