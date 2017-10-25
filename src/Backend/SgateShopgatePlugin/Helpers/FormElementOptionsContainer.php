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

require_once "FormElementScopeLocale.php";
require_once "FormElementScopeShop.php";

/**
 * Represents options for a form element in the Shopware configuration UI.
 *
 * This maps to the "options" field in Shopware's "s_core_config_elements" table.
 *
 * Due to an implementation detail in Shopware the table fields "value", "label", "description", "required", "position"
 * and "scope" are part of those options rather than the form element itself.
 *
 * Consider \Shopware\Models\Models\Element::setOptions() to see how this works in Shopware.
 */
abstract class FormElementOptionsContainer
{
    /**
     * @var array
     */
    protected $data;

    public function __construct()
    {
        // "scope" is the only field that is guaranteed to be present; set to shop scope by default
        $formElementScopeShop = new FormElementScopeShop();

        $this->data = array(
            'scope' => $formElementScopeShop->getScope(),
        );
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->data['value'] = $value;

        return $this;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->data['label'] = $label;

        return $this;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->data['description'] = $description;

        return $this;
    }

    /**
     * @param int|boolean $required
     *
     * @return $this
     */
    public function setRequired($required)
    {
        $this->data['required'] = $required
            ? 1
            : 0;

        return $this;
    }

    /**
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        $this->data['position'] = (int)$position;

        return $this;
    }

    /**
     * @param FormElementScope $scope
     *
     * @return $this
     */
    public function setScope(FormElementScope $scope)
    {
        $this->data['scope'] = $scope->getScope();

        return $this;
    }

    /**
     * Returns an array with all options which are set for this specific option container type.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
