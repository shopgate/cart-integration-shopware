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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Category
{
    const CACHE_KEY_STREAM_CATEGORY_ = 'stream_categories_children_';
    const CACHE_KEY_CATEGORY_ = 'categories_children_';

    /** @var null|\phpFastCache\Core\DriverAbstract */
    private $cacheInstance = null;

    /** @param \phpFastCache\Core\DriverAbstract $cacheInstance */
    public function __construct($cacheInstance = null)
    {
        $this->cacheInstance = $cacheInstance;
    }

    /**
     * @return null|\phpFastCache\Core\DriverAbstract
     */
    private function getCacheInstace()
    {
        return $this->cacheInstance;
    }

    /**
     * Select all child categories by id
     *
     * @param array $categories
     * @param int   $parentID
     *
     * @return array
     */
    public function getCategories($categories = array(), $parentID = 1)
    {
        $cacheKey = self::CACHE_KEY_CATEGORY_ . $parentID;

        if ($this->getCacheInstace() !== null && $this->getCacheInstace()->get($cacheKey)) {
            return $this->getCacheInstace()->get($cacheKey);
        }

        $sql = "
            SELECT DISTINCT id as categoryID, parent as parentID, s_categories.*
            FROM s_categories
            WHERE parent = $parentID
            AND id != parent
            ORDER BY categoryID
        ";
        foreach (Shopware()->Db()->fetchAll($sql) as $row) {
            unset($row['id'], $row['parent']);
            $categories[$row['categoryID']] = $row;
            $categories                     += array_diff_key(
                $this->getCategories($categories, $row['categoryID']),
                $categories
            );
        }

        if ($this->getCacheInstace()) {
            $this->getCacheInstace()->set($cacheKey, $categories);
        }

        return $categories;
    }

    /**
     * Select all child stream categories by id
     *
     * @param array $categories
     * @param int   $parentID
     *
     * @return array
     */
    public function getStreamCategories($categories = array(), $parentID = 1)
    {
        $cacheKey = self::CACHE_KEY_STREAM_CATEGORY_ . $parentID;

        if ($this->getCacheInstace() !== null && $this->getCacheInstace()->get($cacheKey)) {
            return $this->getCacheInstace()->get($cacheKey);
        }

        $sql = "
            SELECT DISTINCT id, stream_id
            FROM s_categories
            WHERE parent = $parentID
            AND id != parent
			AND active = 1
        ";
        foreach (Shopware()->Db()->fetchAll($sql) as $row) {
            if ($row['stream_id'] > 0) {
                $categories[$row['id']] = $row['stream_id'];
            }
            $categories = $this->getStreamCategories($categories, $row['id']);
        }

        if ($this->getCacheInstace()) {
            $this->getCacheInstace()->set($cacheKey, $categories);
        }

        return $categories;
    }
}
