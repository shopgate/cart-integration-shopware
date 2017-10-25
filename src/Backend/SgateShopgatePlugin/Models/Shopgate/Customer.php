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
 * @ORM\Table(name="s_shopgate_customer")
 */
class Customer extends ModelEntity
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
     * @var integer $customerId
     * @ORM\Column(name="userID", type="integer", nullable=false)
     */
    private $customerId;

    /**
     * @var \Shopware\Models\Customer\Customer
     * @ORM\OneToOne(targetEntity="\Shopware\Models\Customer\Customer")
     * @ORM\JoinColumn(name="userID", referencedColumnName="id")
     */
    protected $customer;

    /**
     * @var string $token
     * @ORM\Column(name="token", type="string", length=100, nullable=false)
     */
    private $token;

    public function __construct()
    {
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
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     *
     * @param integer $value
     */
    public function setCustomerId($value)
    {
        $this->customerId = $value;
    }

    /**
     *
     * @return \Shopware\Models\Customer\Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     *
     * @param \Shopware\Models\Customer\Customer $value
     */
    public function setOrder($value)
    {
        $this->customer = $value;
    }

    /**
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     *
     * @param string $value
     */
    public function setToken($value)
    {
        $this->token = $value;
    }

    /**
     *
     * @param \Shopware\Models\Customer\Customer $customer
     */
    public function setTokenByCustomer($customer)
    {
        $this->token = md5($customer->getId() . $customer->getEmail());
    }
}
