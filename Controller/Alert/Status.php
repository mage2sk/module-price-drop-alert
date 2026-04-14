<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Check Price Alert Status Controller
 */

namespace Panth\PriceDropAlert\Controller\Alert;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;

class Status extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var PriceAlertFactory
     */
    protected $priceAlertFactory;

    /**
     * @var PriceAlertHelper
     */
    protected $helper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     * @param PriceAlertFactory $priceAlertFactory
     * @param PriceAlertHelper $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        PriceAlertFactory $priceAlertFactory,
        PriceAlertHelper $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->priceAlertFactory = $priceAlertFactory;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->helper->isPriceAlertEnabled()) {
            return $result->setData([
                'error' => true,
                'subscribed' => false
            ]);
        }

        $productId = (int) $this->getRequest()->getParam('product_id');
        $email = $this->getRequest()->getParam('email');

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'subscribed' => false
            ]);
        }

        // Get customer email if logged in
        if ($this->customerSession->isLoggedIn()) {
            $email = $this->customerSession->getCustomer()->getEmail();
        }

        if (!$email) {
            return $result->setData([
                'subscribed' => false
            ]);
        }

        try {
            // Check if alert exists
            $priceAlert = $this->priceAlertFactory->create();
            $collection = $priceAlert->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('status', \Panth\PriceDropAlert\Model\PriceAlert::STATUS_ACTIVE);

            $subscribed = $collection->getSize() > 0;
            $alertData = null;

            if ($subscribed) {
                $alert = $collection->getFirstItem();
                $alertData = [
                    'alert_id' => $alert->getAlertId(),
                    'subscribed_price' => $alert->getSubscribedPrice(),
                    'trigger_price' => $alert->getTriggerPrice(),
                    'customer_name' => $alert->getCustomerName()
                ];
            }

            return $result->setData([
                'subscribed' => $subscribed,
                'alert' => $alertData
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'subscribed' => false
            ]);
        }
    }
}
