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
        if ($this->isRedirectAllowed()) {
            $oRedirect = $this->getRedirector(
                $this->getConfig()->getRedirectType() ==
                Shopware_Plugins_Backend_SgateShopgatePlugin_Components_Config::REDIRECT_TYPE_HTTP
            );

            $uid = !empty($this->params['sArticle']) ? $this->params['sArticle'] : $this->params['articleId'];

            if (!empty($uid)) {
                $jsHeader = $oRedirect->buildScriptItem($uid);
            } elseif (isset($this->params['sSupplier'])) {
                $sName    = Shopware()->Db()->fetchOne(
                    "SELECT name FROM `s_articles_supplier` WHERE id= " . (int)$this->params['sSupplier']
                );
                $jsHeader = $oRedirect->buildScriptBrand($sName);
            } elseif (isset($this->params['sCategory'])) {
                $jsHeader = $oRedirect->buildScriptCategory($this->params['sCategory']);
            } elseif (isset($this->params['sSearch'])) {
                $jsHeader = $oRedirect->buildScriptSearch($this->params['sSearch']);
            } elseif ($this->request->getControllerName() == 'index') {
                $jsHeader = $oRedirect->buildScriptShop();
            } else {
                $jsHeader = $oRedirect->buildScriptDefault();
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
            || $this->params['module'] == 'api'
            || $this->params['controller'] == 'supplier'
            || $this->params['controller'] == 'captcha'
            || $this->request->getControllerName() == 'AmazonPaymentsAdvanced'
        ) {
            return false;
        }

        // redirect only if all necessary data is given
        $customerNumber = trim($this->getConfig()->getCustomerNumber());
        $shopNumber     = trim($this->getConfig()->getShopNumber());
        $apiKey         = trim($this->getConfig()->getApikey());
        $alias          = trim($this->getConfig()->getAlias());
        $cname          = trim($this->getConfig()->getCname());
        if (empty($customerNumber) || empty($shopNumber) || empty($apiKey) || (empty($alias) && empty($cname))) {
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
                "SELECT blog FROM `s_categories` WHERE id = " . (int)$this->params['sCategory']
            );

            if ($blog) {
                return false;
            }
        }

        if ($this->request->getControllerName() == 'detail') {
            if (!empty($this->params['sArticle'])) {
                $row = Shopware()->Db()->fetchRow(
                    "SELECT mode,id FROM `s_articles` WHERE id = " . (int)$this->params['sArticle']
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
