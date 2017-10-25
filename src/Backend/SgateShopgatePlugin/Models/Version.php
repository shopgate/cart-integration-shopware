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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Models_Version
{
    /** @var string */
    private $shopwareVersion;

    /**
     * @param string $shopwareVersion
     */
    public function __construct($shopwareVersion = null)
    {
        if (empty($shopwareVersion)) {
            $shopwareVersion = Shopware()->Config()->version;
        }

        $this->shopwareVersion = $shopwareVersion;
    }

    /**
     * @param string $requiredVersion The minimum version Shopware should be
     *
     * @return bool true if the installed Shopware version is greater or equal to $version or the Shopware version
     *              constant is undefined; false otherwise
     */
    public function assertMinimum($requiredVersion)
    {
        return (
            $this->shopwareVersion === '___VERSION___'
            || version_compare($this->shopwareVersion, $requiredVersion, '>=')
        );
    }
}
