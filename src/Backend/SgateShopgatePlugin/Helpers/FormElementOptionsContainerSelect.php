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

require_once "FormElementOptionsContainer.php";

class FormElementOptionsContainerSelect extends FormElementOptionsContainer
{
    /**
     * @param string $defaultValue
     *
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        return $this->setValue($defaultValue);
    }

    /**
     * Use "setStore" if providing an array is not enough, like it may be the case for multiselect elements
     *
     * @param array[] $selectionValues
     *
     * @return $this
     */
    public function setSelectionValues(array $selectionValues)
    {
        $this->data['store'] = $selectionValues;

        return $this;
    }

    /**
     * Concurrent method to "setSelectionValues", but more generic and usable with an Ajax string
     *
     * @param mixed $store
     *
     * @return $this
     */
    public function setStore($store)
    {
        $this->data['store'] = $store;

        return $this;
    }

    /**
     * @param string $hiddenName
     *
     * @return $this
     */
    public function setHiddenName($hiddenName)
    {
        $this->data['hiddenName'] = $hiddenName;

        return $this;
    }

    /**
     * @param string $hiddenValue
     *
     * @return $this
     */
    public function setHiddenValue($hiddenValue)
    {
        $this->data['hiddenValue'] = $hiddenValue;

        return $this;
    }

    /**
     * @param string $displayField
     *
     * @return $this
     */
    public function setDisplayField($displayField)
    {
        $this->data['displayField'] = $displayField;

        return $this;
    }

    /**
     * @param bool $multiSelect
     *
     * @return $this
     */
    public function setMultiSelect($multiSelect)
    {
        $this->data['multiSelect'] = $multiSelect;

        return $this;
    }

    /**
     * @param bool $triggerAction
     *
     * @return $this
     */
    public function setTriggerAction($triggerAction)
    {
        $this->data['triggerAction'] = $triggerAction;

        return $this;
    }

    /**
     * Creates an Ajax string to be used in a "store" form element option.
     * Returns an empty string if no fields are given.
     *
     * @param array  $fields
     * @param string $remoteUrl
     *
     * @return string
     */
    public static function buildStoreAjax(array $fields, $remoteUrl)
    {
        if (empty($fields)) {
            return '';
        }

        return
            'new Ext.data.Store({
                fields: ["' . implode('", "', $fields) . '"],
                proxy : {
                    type : "ajax",
                    autoLoad : true,
                    api : {
                        read : "' . $remoteUrl . '",
                    },
                    reader : {
                        type : "json",
                        root : "data"
                    }
                }
            })';
    }
}
