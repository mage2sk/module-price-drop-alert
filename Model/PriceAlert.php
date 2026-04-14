<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Price Alert Model
 */

namespace Panth\PriceDropAlert\Model;

use Magento\Framework\Model\AbstractModel;

class PriceAlert extends AbstractModel
{
    /**
     * Status constants
     */
    const STATUS_ACTIVE = 1;
    const STATUS_SENT = 2;
    const STATUS_CANCELLED = 3;

    /**
     * Cache tag
     */
    const CACHE_TAG = 'panth_price_alert';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'panth_price_alert';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Panth\PriceDropAlert\Model\ResourceModel\PriceAlert::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get alert ID
     *
     * @return int|null
     */
    public function getAlertId()
    {
        return $this->getData('alert_id');
    }

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * Get product ID
     *
     * @return int
     */
    public function getProductId()
    {
        return $this->getData('product_id');
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->getData('email');
    }

    /**
     * Get customer name
     *
     * @return string|null
     */
    public function getCustomerName()
    {
        return $this->getData('customer_name');
    }

    /**
     * Get target price
     *
     * @return float
     */
    public function getTargetPrice()
    {
        return $this->getData('target_price');
    }

    /**
     * Get trigger price (alias for getTargetPrice)
     *
     * @return float
     */
    public function getTriggerPrice()
    {
        return $this->getTargetPrice();
    }

    /**
     * Get subscribed price (current product price when alert was created)
     * Note: This is stored as target_price in the database
     *
     * @return float
     */
    public function getSubscribedPrice()
    {
        return $this->getData('subscribed_price');
    }

    /**
     * Get store ID
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Get sent at
     *
     * @return string|null
     */
    public function getSentAt()
    {
        return $this->getData('sent_at');
    }

    /**
     * Set alert ID
     *
     * @param int $alertId
     * @return $this
     */
    public function setAlertId($alertId)
    {
        return $this->setData('alert_id', $alertId);
    }

    /**
     * Set customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * Set product ID
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        return $this->setData('product_id', $productId);
    }

    /**
     * Set email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        return $this->setData('email', $email);
    }

    /**
     * Set customer name
     *
     * @param string|null $customerName
     * @return $this
     */
    public function setCustomerName($customerName)
    {
        return $this->setData('customer_name', $customerName);
    }

    /**
     * Set target price
     *
     * @param float $targetPrice
     * @return $this
     */
    public function setTargetPrice($targetPrice)
    {
        return $this->setData('target_price', $targetPrice);
    }

    /**
     * Set trigger price (alias for setTargetPrice)
     *
     * @param float $triggerPrice
     * @return $this
     */
    public function setTriggerPrice($triggerPrice)
    {
        return $this->setTargetPrice($triggerPrice);
    }

    /**
     * Set subscribed price (current product price when alert was created)
     *
     * @param float $subscribedPrice
     * @return $this
     */
    public function setSubscribedPrice($subscribedPrice)
    {
        return $this->setData('subscribed_price', $subscribedPrice);
    }

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * Set status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Set sent at
     *
     * @param string|null $sentAt
     * @return $this
     */
    public function setSentAt($sentAt)
    {
        return $this->setData('sent_at', $sentAt);
    }
}
