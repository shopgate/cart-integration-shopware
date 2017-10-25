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

namespace Shopware\Bundle\SearchBundle;

class StoreFrontCriteriaFactory implements StoreFrontCriteriaFactoryInterface
{
    const SORTING_RELEASE_DATE      = 1;
    const SORTING_POPULARITY        = 2;
    const SORTING_CHEAPEST_PRICE    = 3;
    const SORTING_HIGHEST_PRICE     = 4;
    const SORTING_PRODUCT_NAME_ASC  = 5;
    const SORTING_PRODUCT_NAME_DESC = 6;
    const SORTING_SEARCH_RANKING    = 7;
}
