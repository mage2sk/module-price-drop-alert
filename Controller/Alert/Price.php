<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Subscribe to Price Alert Controller
 */

namespace Panth\PriceDropAlert\Controller\Alert;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Price extends Action implements HttpPostActionInterface
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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PriceAlertFactory
     */
    protected $priceAlertFactory;

    /**
     * @var PriceAlertHelper
     */
    protected $helper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManager
     * @param PriceAlertFactory $priceAlertFactory
     * @param PriceAlertHelper $helper
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        PriceAlertFactory $priceAlertFactory,
        PriceAlertHelper $helper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->priceAlertFactory = $priceAlertFactory;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
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
        $customerName = $this->getRequest()->getParam('customer_name');
        $triggerPrice = $this->getRequest()->getParam('trigger_price');

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'message' => __('Product ID is required.')
            ]);
        }

        // Get customer ID if logged in
        $customerId = $this->customerSession->isLoggedIn()
            ? $this->customerSession->getCustomerId()
            : null;

        // If guest and no email provided
        if (!$customerId && !$email) {
            return $result->setData([
                'error' => true,
                'message' => __('Email is required for guests.')
            ]);
        }

        // Validate customer name for guests
        if (!$customerId && !$customerName) {
            return $result->setData([
                'error' => true,
                'message' => __('Name is required for guests.')
            ]);
        }

        // Validate customer name length
        if ($customerName && mb_strlen($customerName) > 255) {
            return $result->setData([
                'error' => true,
                'message' => __('Name is too long. Maximum 255 characters allowed.')
            ]);
        }

        // Sanitize customer name to prevent XSS
        if ($customerName) {
            $customerName = strip_tags($customerName);
            $customerName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
            // Trim whitespace
            $customerName = trim($customerName);

            // Validate name is not empty after sanitization
            if (!$customerId && empty($customerName)) {
                return $result->setData([
                    'error' => true,
                    'message' => __('Please enter a valid name.')
                ]);
            }
        }

        // Validate email format
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $result->setData([
                'error' => true,
                'message' => __('Please enter a valid email address.')
            ]);
        }

        // If logged in, use customer email and name
        if ($customerId) {
            $email = $this->customerSession->getCustomer()->getEmail();
            $customerName = $this->customerSession->getCustomer()->getFirstname() . ' ' .
                           $this->customerSession->getCustomer()->getLastname();
        }

        try {
            $product = $this->productRepository->getById($productId);
            $currentPrice = $product->getFinalPrice();

            // Validate trigger price
            if ($triggerPrice !== null && $triggerPrice !== '') {
                $triggerPrice = (float) $triggerPrice;
                if ($triggerPrice <= 0) {
                    return $result->setData([
                        'error' => true,
                        'message' => __('Please enter a valid target price.')
                    ]);
                }
                if ($triggerPrice >= $currentPrice) {
                    return $result->setData([
                        'error' => true,
                        'message' => __('Target price must be lower than current price.')
                    ]);
                }
            } else {
                $triggerPrice = null;
            }

            // Check if alert already exists
            $priceAlert = $this->priceAlertFactory->create();
            $collection = $priceAlert->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('status', \Panth\PriceDropAlert\Model\PriceAlert::STATUS_ACTIVE);

            if ($collection->getSize() > 0) {
                return $result->setData([
                    'error' => true,
                    'message' => __('You are already subscribed to price alerts for this product.')
                ]);
            }

            // Create new alert
            $priceAlert = $this->priceAlertFactory->create();
            $priceAlert->setCustomerId($customerId)
                ->setProductId($productId)
                ->setSubscribedPrice($currentPrice)
                ->setTriggerPrice($triggerPrice)
                ->setEmail($email)
                ->setCustomerName($customerName)
                ->setStoreId($this->storeManager->getStore()->getId())
                ->setStatus(\Panth\PriceDropAlert\Model\PriceAlert::STATUS_ACTIVE)
                ->save();

            $message = $triggerPrice
                ? __('You will be notified when the price drops to %1 or below.',
                     $this->storeManager->getStore()->getCurrentCurrency()->format($triggerPrice, [], false))
                : __('You will be notified when the price drops for this product.');

            return $result->setData([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
