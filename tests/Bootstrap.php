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

require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/vendor/autoload.php';
require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/Components/Config.php';
require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/Plugin.php';
require_once dirname(__FILE__)
    . '/../src/Backend/SgateShopgatePlugin/Components/EventHandler/ShopwareModulesOrderSendMailSend.php';
require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/Models/Export/Product.php';
require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/Models/Translation.php';
require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/Models/Version.php';
require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/Models/Sort/ArticleInterface.php';
require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/Models/Sort/ArticleLegacy.php';
require_once dirname(__FILE__) . '/../src/Backend/SgateShopgatePlugin/Models/Sort/Article.php';
require_once dirname(__FILE__) . '/Stubs/Shopware/Models/Search/CustomSorting.php';
require_once dirname(__FILE__) . '/Stubs/Shopware/Bundle/SearchBundle/SortingInterface.php';
require_once dirname(__FILE__) . '/Stubs/Shopware/Bundle/SearchBundle/StoreFrontCriteriaFactoryInterface.php';
require_once dirname(__FILE__) . '/Stubs/Shopware/Bundle/SearchBundle/StoreFrontCriteriaFactory.php';
require_once dirname(__FILE__) . '/Stubs/Shopware/Bundle/SearchBundle/Sorting/PopularitySorting.php';
