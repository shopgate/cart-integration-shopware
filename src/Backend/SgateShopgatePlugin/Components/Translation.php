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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Translation
{
    /**
     * @param \Shopware\Models\Config\Form $form
     */
    public function applyTranslations(\Shopware\Models\Config\Form $form)
    {
        $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
        $translations   = $this->getTranslations();

        foreach ($translations as $locale => $snippets) {
            $localeModel = $shopRepository->findOneBy(
                array(
                    'locale' => $locale,
                )
            );

            if ($localeModel === null) {
                continue;
            }

            foreach ($snippets as $element => $snippet) {
                $elementModel = $form->getElement($element);
                if ($elementModel === null) {
                    continue;
                }
                $this->deleteElementTranslationEntries($elementModel->getId(), $localeModel->getId());

                $elementModel->addTranslation(
                    $this->createElementTranslationModel(
                        $snippet['label'],
                        $snippet['description'],
                        $localeModel
                    )
                );
            }
        }
    }

    /**
     * delete entries in database table s_core_config_element_translations
     *
     * @param int $elementModelId
     * @param int $localeModelId
     */
    protected function deleteElementTranslationEntries($elementModelId, $localeModelId)
    {
        if (empty($elementModelId)) {
            return;
        }

        $sql = "DELETE FROM `s_core_config_element_translations` WHERE `element_id` = ? AND locale_id = ?;";
        Shopware()->Db()->query(
            $sql,
            array((int)$elementModelId, $localeModelId)
        );
    }

    /**
     * @param string                       $label
     * @param string                       $description
     * @param \Shopware\Models\Shop\Locale $localeModel
     *
     * @return \Shopware\Models\Config\ElementTranslation
     */
    protected function createElementTranslationModel($label, $description, $localeModel)
    {
        $translationModel = new \Shopware\Models\Config\ElementTranslation();

        $translationModel->setLabel($label);
        $translationModel->setDescription($description);
        $translationModel->setLocale($localeModel);

        return $translationModel;
    }

    /**
     * Returns array with all translated form data:
     *
     * locale
     *      label
     *      description
     *
     * @return array
     */
    protected function getTranslations()
    {
        return array(
            'en_GB' => array(
                'SGATE_IS_ACTIVE'                   => array('label' => 'Active'),
                'SGATE_CUSTOMER_NUMBER'             => array(
                    'label'       => 'Customer number',
                    'description' => 'You can find your customer number at the "Integration" section of your merchant area.',
                ),
                'SGATE_SHOP_NUMBER'                 => array(
                    'label'       => 'Shop number',
                    'description' => 'You can find the shop number at the "Integration" section of your merchant area.',
                ),
                'SGATE_API_KEY'                     => array(
                    'label'       => 'API key',
                    'description' => 'You can find the API key at the "Integration" section of your merchant area.',
                ),
                'SGATE_ALIAS'                       => array(
                    'label'       => 'Shop alias',
                    'description' => 'You can find the alias at the "Integration" section of your merchant area.',
                ),
                'SGATE_ROOT_CATEGORY'               => array(
                    'label'       => 'Root Category',
                    'description' => 'Select the root category of your shop. This information is used for the category and product export.',
                ),
                'SGATE_CNAME'                       => array(
                    'label'       => 'CNAME',
                    'description' => 'Enter a custom URL (defined by CNAME) for your mobile website. You can find the URL at the "Integration" section of your shop after you activated this option in the "Settings" => "Mobile website / webapp" section.',
                ),
                'SGATE_REDIRECT_TYPE'               => array(
                    'label'       => 'Redirect Type',
                    'description' => 'Change this only if you use external caching software or engines and experience problems with the HTTP redirect.',
                ),
                'SGATE_REDIRECT_INDEX_BLOG'         => array(
                    'label'       => 'Redirect Hompage is Blog ',
                    'description' => 'Allow redirect if Hompage is of type Blog',
                ),
                'SGATE_STATUS_SHIPPING_NOT_BLOCKED' => array('label' => 'Order state if shipping is not blocked'),
                'SGATE_FIXED_SHIPPING_SERVICE'      => array(
                    'label'       => 'Shipping method for incoming orders',
                    'description' => 'If the shipping method of the incoming order could not be mapped, this shipping method will be used instead.',
                ),
                'SGATE_SEND_ORDER_MAIL'             => array(
                    'label'       => 'Send order confirmation mail to customer',
                    'description' => 'Let shopware send email notification for new imported orders placed by Shopgate.',
                ),
                'SGATE_EXPORT_ATTRIBUTES'           => array(
                    'label'       => 'Attribute ids for product export',
                    'description' => 'All attribute ids will be exported in the property section of the product, format e. g. 3 for attr3.',
                ),
                'SGATE_SERVER'                      => array(
                    'label'       => 'Shopgate server',
                    'description' => 'Only change this setting after contacting Shopgate!',
                ),
                'SGATE_SERVER_URL'                  => array(
                    'label'       => 'API URL',
                    'description' => 'Only change this setting after contacting Shopgate!',
                ),
                'SGATE_EXPORT_PRODUCT_DESCRIPTION'  => array(
                    'label'       => 'Products description layout',
                    'description' => 'Determines which field is used for the product description during export',
                ),
                'SGATE_EXPORT_PRODUCT_DOWNLOADS'    => array(
                    'label'       => 'Products downloads export',
                    'description' => 'Determines if and where product downloads are exported',
                ),
                'SGATE_EXPORT_PRODUCT_INACTIVE'    => array(
                    'label'       => 'Export inactive products',
                ),
                'SGATE_EXPORT_DIMENSION_UNIT'      => array(
                    'label'       => 'Product dimension units'
                ),
                'SGATE_EXPORT_ATTRIBUTES_AS_DESCRIPTION'      => array(
                    'label'       => 'Append attributes to description',
                    'description' => 'Select a list of attributes that should also be appended to the description.',
                ),
                'SGATE_CUSTOM_CSS'                  => array(
                    'label'       => 'Custom css',
                    'description' => 'Here you can save CSS customizations for the webcheckout of your Shopgate app',
                )
            ),
        );
    }
}
