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

namespace Shopgate\Helpers;

use Shopware\Bundle\AttributeBundle\Service\ConfigurationStruct;
use Shopware\Bundle\AttributeBundle\Service\CrudService;

class Attribute
{
    const ARTICLE_ATTRIBUTE_TABLE = 's_articles_attributes';

    /**
     * @param bool $useFlatLabel
     *
     * @return array
     */
    public function getConfiguredAttributes($useFlatLabel = true)
    {
        $configuredAttributes = array();

        if (version_compare(Shopware()->Config()->version, '5.2', '<')) {
            return $this->getLegacyConfiguredAttributes();
        }

        /** @var CrudService $crudService */
        $crudService = Shopware()->Container()->get('shopware_attribute.crud_service');
        $attributes  = $crudService->getList(self::ARTICLE_ATTRIBUTE_TABLE);

        $attributes = array_filter(
            $attributes,
            function (ConfigurationStruct $column) {
                return $column->isConfigured() == true && $column->isIdentifier() == false;
            }
        );

        foreach ($attributes as $attribute) {
            if ($useFlatLabel) {
                $configuredAttributes[$attribute->getColumnName()] = $attribute->getLabel();
            } else {
                $configuredAttributes[] = array($attribute->getLabel(), $attribute->getLabel());
            }
        }

        return $configuredAttributes;
    }

    /**
     * Export the configured attributes for Shopware below 5.2
     *
     * @return array
     */
    protected function getLegacyConfiguredAttributes()
    {
        $configuredAttributes = array();
        $elements             = Shopware()->Models()
                                          ->createQuery("SELECT e FROM \Shopware\Models\Article\Element e")
                                          ->getResult();

        foreach ($elements as $element) {
            $configuredAttributes[$element->getName()] = $element->getLabel();
        }

        return $configuredAttributes;
    }
}
