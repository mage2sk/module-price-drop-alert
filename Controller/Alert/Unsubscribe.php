<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Unsubscribe from Price Alert Controller
 */

namespace Panth\PriceDropAlert\Controller\Alert;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;

class Unsubscribe extends Action implements HttpPostActionInterface
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
                'message' => __('Price alerts are disabled.')
            ]);
        }

        $productId = (int) $this->getRequest()->getParam('product_id');
        $email = $this->getRequest()->getParam('email');

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'message' => __('Product ID is required.')
            ]);
        }

        // Get customer email if logged in
        if ($this->customerSession->isLoggedIn()) {
            $email = $this->customerSession->getCustomer()->getEmail();
        }

        if (!$email) {
            return $result->setData([
                'error' => true,
                'message' => __('Email is required.')
            ]);
        }

        try {
            // Find and delete the alert
            $priceAlert = $this->priceAlertFactory->create();
            $collection = $priceAlert->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('status', \Panth\PriceDropAlert\Model\PriceAlert::STATUS_ACTIVE);

            if ($collection->getSize() === 0) {
                return $result->setData([
                    'error' => true,
                    'message' => __('No active price alert found for this product.')
                ]);
            }

            // Delete all active alerts for this product/email
            foreach ($collection as $alert) {
                $alert->delete();
            }

            return $result->setData([
                'success' => true,
                'message' => __('You have been unsubscribed from price alerts for this product.')
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
