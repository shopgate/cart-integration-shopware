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

/**
 * Represents a form element in the Shopware configuration UI.
 *
 * This maps to the fields in Shopware's "s_core_config_elements" table.
 */
abstract class FormElement
{
    const VISIBILITY_VISIBLE = 'visible';
    const VISIBILITY_HIDDEN  = 'hidden';

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $visibility;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param string $key
     * @param string $visibility One of the FormElement::VISIBILITY_* constants.
     */
    public function __construct($key = "", $visibility = self::VISIBILITY_VISIBLE)
    {
        $this->key        = $key;
        $this->visibility = $visibility;
        $this->options    = $this->getOptions();
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->getOption('value');
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->getOption('label');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getOption('description');
    }

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return int
     */
    public function getRequired()
    {
        return $this->getOption('required');
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->getOption('position');
    }

    /**
     * @return int
     */
    public function getScope()
    {
        return $this->getOption('scope');
    }

    /**
     * Sets the visibility for this particular FormElement.
     *
     * @param string $visibility One of the FormElement::VISIBILITY_* constants.
     *
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @return bool
     */
    public function isVisibile()
    {
        return ($this->visibility == self::VISIBILITY_VISIBLE);
    }

    /**
     * An array with at least default fields is guaranteed to be returned.
     *
     * @return array
     */
    public function getOptions()
    {
        $elementOptions       = isset($this->options)
            ? $this->options
            : array();
        $formElementScopeShop = new FormElementScopeShop();

        if (empty($elementOptions['value'])) {
            $elementOptions['value'] = '';
        }
        if (empty($elementOptions['label'])) {
            $elementOptions['label'] = '';
        }
        if (empty($elementOptions['description'])) {
            $elementOptions['description'] = '';
        }
        if (empty($elementOptions['required'])) {
            $elementOptions['required'] = 0;
        }
        if (empty($elementOptions['position'])) {
            $elementOptions['position'] = 0;
        }
        if (empty($elementOptions['scope'])) {
            $elementOptions['scope'] = $formElementScopeShop->getScope();
        }

        return $elementOptions;
    }

    /**
     * Returns the value of a particular option or null if it wasn't found.
     *
     * @param string $optionName
     *
     * @return mixed|null The option value or null if $optionName was empty or the option was not found.
     */
    public function getOption($optionName)
    {
        if ($optionName === '') {
            return null;
        }

        $elementOptions = $this->getOptions();

        return isset($elementOptions[$optionName])
            ? $elementOptions[$optionName]
            : null;
    }
}
