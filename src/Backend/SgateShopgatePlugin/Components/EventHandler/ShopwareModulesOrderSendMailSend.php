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

/**
 * Handles the "Shopware_Modules_Order_SendMail_Send" event from Shopware for orders imported by the Shopgate plugin.
 *
 * The handlers tries to influence sending order confirmation emails. It will deactivate sending confirmation emails
 * if turned off in the plugin configuration. It indicate to execute the default behavior if not turned off.
 *
 */
class Shopware_Plugins_Backend_SgateShopgatePlugin_Components_EventHandler_ShopwareModulesOrderSendMailSend
{
    /** @var bool */
    private $shopgateConfigValueSendEmail;

    /**
     * @param bool $shopgateConfigValueSendEmail
     */
    public function __construct($shopgateConfigValueSendEmail)
    {
        $this->shopgateConfigValueSendEmail = $shopgateConfigValueSendEmail;
    }

    /**
     * Tries to influence whether a confirmation email is sent when importing orders.
     *
     * The event is called from /engine/core/class/sOrder.php.
     *
     * Sending out emails can be disabled by this handler by returning anything !== null.
     * Sending out emails can not be enforced by this handler but can be allowed by returning null.
     *
     * Note that when returning null, subsequent handlers for this event could still turn off the email sending process.
     * Also, there is a global configuration setting in Shopware that can turn off sending emails all together.
     *
     * @return bool|null Boolean false if sending emails is disabled or null if it's enabled.
     */
    public function handle()
    {
        return $this->shopgateConfigValueSendEmail
            ? null
            : false;
    }
}
