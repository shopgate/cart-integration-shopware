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

namespace Shopware\CustomModels\Shopgate;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @category  Shopware
 * @package   Shopware\Plugins\SgateShopgatePlugin\Models
 *
 * @ORM\Entity
 * @ORM\Table(name="s_shopgate_orders")
 */
class Order extends ModelEntity
{
    /**
     * Unique identifier field.
     *
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $orderId
     * @ORM\Column(name="orderID", type="integer", nullable=false)
     */
    private $orderId;

    /**
     * @var \Shopware\Models\Order\Order
     * @ORM\OneToOne(targetEntity="\Shopware\Models\Order\Order")
     * @ORM\JoinColumn(name="orderID", referencedColumnName="id")
     */
    protected $order;

    /**
     * @var string $order_number
     * @ORM\Column(name="shopgate_order_number", type="string", length=20, nullable=false)
     */
    private $order_number;

    /**
     * @var boolean $is_shipping_blocked
     * @ORM\Column(name="is_shipping_blocked", type="boolean", nullable=false)
     */
    private $is_shipping_blocked;

    /**
     * @var boolean $is_sent_to_shopgate
     * @ORM\Column(name="is_sent_to_shopgate", type="boolean", nullable=false)
     */
    private $is_sent_to_shopgate;

    /**
     * @var boolean $is_cancellation_sent_to_shopgate
     * @ORM\Column(name="is_cancellation_sent_to_shopgate", type="boolean", nullable=false)
     */
    private $is_cancellation_sent_to_shopgate;

    /**
     * @var string $reported_cancellations
     * @ORM\Column(name="reported_cancellations", type="text", nullable=false)
     */
    private $reported_cancellations;

    /**
     * @var string $received_data
     * @ORM\Column(name="received_data", type="text", nullable=false)
     */
    private $received_data;

    /**
     * @var string $order_item_map
     * @ORM\Column(name="order_item_map", type="text", nullable=false)
     */
    private $order_item_map;

    public function __construct()
    {
        $this->is_shipping_blocked              = true;
        $this->is_sent_to_shopgate              = false;
        $this->is_cancellation_sent_to_shopgate = false;

        $this->setReportedCancellations(array());
    }

    /**
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @param integer $value
     */
    public function setId($value)
    {
        $this->id = $value;
    }

    /**
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     *
     * @param integer $value
     */
    public function setOrderId($value)
    {
        $this->orderId = $value;
    }

    /**
     *
     * @return \Shopware\Models\Order\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     *
     * @param \Shopware\Models\Order\Order $value
     */
    public function setOrder($value)
    {
        $this->order = $value;
    }

    /**
     *
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->order_number;
    }

    /**
     *
     * @param string $value
     */
    public function setOrderNumber($value)
    {
        $this->order_number = $value;
    }

    /**
     *
     * @return boolean
     */
    public function getIsShippingBlocked()
    {
        return $this->is_shipping_blocked;
    }

    /**
     *
     * @param boolean $value
     */
    public function setIsShippingBlocked($value)
    {
        $this->is_shipping_blocked = $value;
    }

    /**
     *
     * @return boolean
     */
    public function getIsSentToShopgate()
    {
        return $this->is_sent_to_shopgate;
    }

    /**
     *
     * @param boolean $value
     */
    public function setIsSentToShopgate($value)
    {
        $this->is_sent_to_shopgate = $value;
    }

    /**
     *
     * @return boolean
     */
    public function getIsCancellationSentToShopgate()
    {
        return $this->is_cancellation_sent_to_shopgate;
    }

    /**
     *
     * @param boolean $value
     */
    public function setIsCancellationSentToShopgate($value)
    {
        $this->is_cancellation_sent_to_shopgate = $value;
    }

    /**
     *
     * @return array
     */
    public function getReportedCancellations()
    {
        return unserialize($this->reported_cancellations);
    }

    /**
     *
     * @param array $value
     */
    public function setReportedCancellations($value)
    {
        $this->reported_cancellations = serialize($value);
    }

    /**
     *
     * @return \ShopgateOrder
     */
    public function getReceivedData()
    {
        return unserialize($this->received_data);
    }

    /**
     *
     * @param \ShopgateOrder $value
     */
    public function setReceivedData($value)
    {
        $this->received_data = serialize($value);
    }

    /**
     *
     * @return array
     */
    public function getOrderItemMap()
    {
        return unserialize($this->order_item_map);
    }

    /**
     *
     * @param array $value
     */
    public function setOrderItemMap($value)
    {
        $this->order_item_map = serialize($value);
    }

    /**
     *
     * @param \ShopgateOrder $shopgateOrder
     */
    public function fromShopgateOrder(\ShopgateOrder $shopgateOrder)
    {
        $this->setReceivedData($shopgateOrder);
        $this->setIsShippingBlocked($shopgateOrder->getIsShippingBlocked());
        $this->setOrderNumber($shopgateOrder->getOrderNumber());
    }
}
