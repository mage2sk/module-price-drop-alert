<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Alert View Block
 */

namespace Panth\PriceDropAlert\Block\Adminhtml\Alert;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class View extends Template
{
    protected $coreRegistry;
    protected $productRepository;
    protected $customerRepository;
    protected $priceCurrency;
    private PriceResolver $priceResolver;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        PriceCurrencyInterface $priceCurrency,
        PriceResolver $priceResolver,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->priceCurrency = $priceCurrency;
        $this->priceResolver = $priceResolver;
        parent::__construct($context, $data);
    }

    /**
     * Get current alert
     *
     * @return PriceAlert
     */
    public function getAlert()
    {
        return $this->coreRegistry->registry('pricedropalert_alert');
    }

    /**
     * Get product
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct()
    {
        try {
            return $this->productRepository->getById($this->getAlert()->getProductId());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        $alert = $this->getAlert();

        if ($alert->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($alert->getCustomerId());
                return $customer->getEmail();
            } catch (\Exception $e) {
                // Fall through to email field
            }
        }

        return $alert->getEmail() ?: __('N/A');
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $alert = $this->getAlert();

        switch ($alert->getStatus()) {
            case PriceAlert::STATUS_ACTIVE:
                return __('Active/Pending');
            case PriceAlert::STATUS_SENT:
                return __('Sent/Notified');
            case PriceAlert::STATUS_CANCELLED:
                return __('Cancelled');
            default:
                return __('Unknown');
        }
    }

    /**
     * Get back URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    /**
     * Get delete URL
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['alert_id' => $this->getAlert()->getId()]);
    }

    /**
     * Get send email URL
     *
     * @return string
     */
    public function getSendEmailUrl()
    {
        return $this->getUrl('*/*/send', ['alert_id' => $this->getAlert()->getId()]);
    }

    /**
     * Format price using store currency
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price)
    {
        return $this->priceCurrency->format($price, false);
    }

    /**
     * Get the current product price (handles configurable/simple)
     *
     * @return float
     */
    public function getCurrentProductPrice()
    {
        $product = $this->getProduct();
        if (!$product) {
            return 0;
        }
        return $this->priceResolver->getPrice($product);
    }
}
