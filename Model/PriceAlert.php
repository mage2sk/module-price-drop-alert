<?php
namespace Panth\PriceDropAlert\Model;

use Magento\Framework\Model\AbstractModel;

class PriceAlert extends AbstractModel
{
    const STATUS_ACTIVE = 1;
    const STATUS_SENT = 2;
    const STATUS_CANCELLED = 3;

    const CACHE_TAG = 'panth_price_alert';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_price_alert';

    protected function _construct()
    {
        $this->_init(\Panth\PriceDropAlert\Model\ResourceModel\PriceAlert::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getAlertId()
    {
        return $this->getData('alert_id');
    }

    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    public function getProductId()
    {
        return $this->getData('product_id');
    }

    public function getEmail()
    {
        return $this->getData('email');
    }

    public function getCustomerName()
    {
        return $this->getData('customer_name');
    }

    public function getTargetPrice()
    {
        return $this->getData('target_price');
    }

    public function getTriggerPrice()
    {
        return $this->getTargetPrice();
    }

    public function getSubscribedPrice()
    {
        return $this->getData('subscribed_price');
    }

    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    public function getStatus()
    {
        return $this->getData('status');
    }

    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    public function getSentAt()
    {
        return $this->getData('sent_at');
    }

    public function getUnsubscribeToken()
    {
        return $this->getData('unsubscribe_token');
    }

    public function setAlertId($alertId)
    {
        return $this->setData('alert_id', $alertId);
    }

    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    public function setProductId($productId)
    {
        return $this->setData('product_id', $productId);
    }

    public function setEmail($email)
    {
        return $this->setData('email', $email);
    }

    public function setCustomerName($customerName)
    {
        return $this->setData('customer_name', $customerName);
    }

    public function setTargetPrice($targetPrice)
    {
        return $this->setData('target_price', $targetPrice);
    }

    public function setTriggerPrice($triggerPrice)
    {
        return $this->setTargetPrice($triggerPrice);
    }

    public function setSubscribedPrice($subscribedPrice)
    {
        return $this->setData('subscribed_price', $subscribedPrice);
    }

    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    public function setSentAt($sentAt)
    {
        return $this->setData('sent_at', $sentAt);
    }
}
