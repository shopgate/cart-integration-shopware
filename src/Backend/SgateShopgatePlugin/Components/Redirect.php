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

class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Redirect
{
    /** @var Enlight_Controller_EventArgs */
    private $args;

    /** @var Enlight_Controller_Request_RequestHttp */
    private $request;

    /** @var array [string, mixed] */
    private $params;

    /**
     * @param Enlight_Controller_EventArgs $args
     */
    public function __construct(&$args)
    {
        $this->args    = $args;
        $this->request = $this->args->getRequest();
        $this->params  = $this->request->getParams();
    }

    /**
     * checks if redirect is allowed and adds corresponding header
     */
    public function redirect()
    {
        // redirect is not allowed in blog sections
        if ($this->isRedirectAllowed()) {
            $oRedirect = $this->getRedirector(
                $this->getConfig()->getRedirectType() ==
                Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::REDIRECT_TYPE_HTTP
            );
            $jsHeader  = "";

            if ($this->request->getControllerName() == 'detail') {
                $uid      = $this->_getRedirectProductUid($this->params);
                $jsHeader = !empty($uid)
                    ? $oRedirect->buildScriptItem($uid)
                    : $oRedirect->buildScriptDefault();
            } else {
                if ($this->request->getControllerName() == 'index') {
                    $jsHeader = $oRedirect->buildScriptShop();
                } else {
                    if ($this->request->getControllerName() == 'listing' && isset($this->params['sSupplier'])) {
                        $sName    = Shopware()->Db()->fetchOne(
                            "SELECT name FROM `s_articles_supplier` WHERE id={$this->params['sSupplier']}"
                        );
                        $jsHeader = $oRedirect->buildScriptBrand($sName);
                    } else {
                        if ($this->request->getControllerName() == 'listing' && isset($this->params['sCategory'])) {
                            $jsHeader = $oRedirect->buildScriptCategory($this->params['sCategory']);
                        } else {
                            if ($this->request->getControllerName() == 'search' && isset($this->params['sSearch'])) {
                                $jsHeader = $oRedirect->buildScriptSearch($this->params['sSearch']);
                            } else {
                                $jsHeader = $oRedirect->buildScriptDefault();
                            }
                        }
                    }
                }
            }

            $jsHeader = addcslashes($jsHeader, "\\");
            $sContent = $this->args->getSubject()->Response()->getBody();
            $sHeader  = null;
            if (preg_match("#</head>#i", $sContent, $sHeader)) {
                $sHeader      = $sHeader[0];
                $sReplacement = $jsHeader . "\n" . $sHeader;

                $sContent = preg_replace("#$sHeader#i", $sReplacement, $sContent);
                $this->args->getSubject()->Response()->setBody($sContent);
            }
        }
    }

    /**
     * @param bool $autoRedirect
     *
     * @return Shopgate_Helper_Redirect_MobileRedirect
     */
    protected function getRedirector($autoRedirect)
    {
        $config   = $this->getConfig();
        $builder  = new ShopgateBuilder($config);
        $redirect = $builder->buildMobileRedirect(
            $this->request->getHeader('User-Agent'),
            $this->request->getParams(),
            $this->request->getCookie()
        );

        $redirect->supressRedirectTechniques(!$autoRedirect, false);

        return $redirect;
    }

    /**
     *
     * @return Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config
     */
    protected function getConfig()
    {
        static $config = null;

        if (!$config) {
            $config = new Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config();
        }

        return $config;
    }

    /**
     * Return the Article Ordernumber of the given Article
     *
     * @param array $params
     *
     * @return string|bool
     */
    protected function _getRedirectProductUid($params)
    {
        $query = Shopware()->Db()->query(
            'SELECT ordernumber 
                  FROM s_articles_details 
                  WHERE id = ?',
            array($params['sArticle'])
        );

        return $params['sArticle'] . '-' . $query->fetchColumn();
    }

    /**
     * @return bool
     */
    protected function isRedirectAllowed()
    {
        if (defined("_SHOPGATE_API") && _SHOPGATE_API) {
            return false;
        }

        if (!$this->getConfig()->getIsActive()) {
            return false;
        }

        if ($this->params['module'] == 'backend'
            || $this->params['controller'] == 'supplier'
            || $this->params['module'] == 'api'
            || $this->request->getControllerName() == 'AmazonPaymentsAdvanced'
        ) {
            return false;
        }

        // redirect only if all necessary data is given
        $customerNumber = trim($this->getConfig()->getCustomerNumber());
        $shopNumber     = trim($this->getConfig()->getShopNumber());
        $apiKey         = trim($this->getConfig()->getApikey());
        $shopAlias      = trim($this->getConfig()->getAlias());
        $shopCname      = trim($this->getConfig()->getCname());
        if (empty($customerNumber) || empty($shopNumber) || empty($apiKey)
            || (empty($shopAlias)
                && empty($shopCname))
        ) {
            return false;
        }

        try {
            if ($this->request->getControllerName() == 'index' && Shopware()->Shop()->getCategory()->getBlog()
                && !$this->getConfig()->getRedirectIndexBlogPage()
            ) {
                // do not redirect in case of the main category being marked as blog
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        if ($this->params['sCategory']) {
            $blog = Shopware()->Db()->fetchOne(
                "SELECT blog FROM `s_categories` WHERE id = {$this->params['sCategory']}"
            );

            if ($blog) {
                return false;
            }
        }

        if ($this->request->getControllerName() == 'detail') {
            if (!empty($this->params['sArticle'])) {
                $row = Shopware()->Db()->fetchRow(
                    "SELECT mode,id FROM `s_articles` WHERE id = {$this->params['sArticle']}"
                );
                if (empty($row)
                    || $row['mode']
                ) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }
}
