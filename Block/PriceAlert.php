<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Price Alert Block
 */

namespace Panth\PriceDropAlert\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Panth\PriceDropAlert\Model\Config\Source\Placement as PlacementSource;
use Panth\PriceDropAlert\Model\PriceResolver;

class PriceAlert extends Template
{
    protected $helper;
    protected $customerSession;
    protected $registry;
    protected $productRepository;
    protected $pricingHelper;
    protected $placementSource;
    private PriceResolver $priceResolver;

    public function __construct(
        Context $context,
        PriceAlertHelper $helper,
        CustomerSession $customerSession,
        Registry $registry,
        ProductRepositoryInterface $productRepository,
        PricingHelper $pricingHelper,
        PlacementSource $placementSource,
        PriceResolver $priceResolver,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->pricingHelper = $pricingHelper;
        $this->placementSource = $placementSource;
        $this->priceResolver = $priceResolver;
        parent::__construct($context, $data);
    }

    /**
     * Check if Price Alert is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->helper->isPriceAlertEnabled();
    }

    /**
     * Get current product
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        if ($this->hasData('product')) {
            return $this->getData('product');
        }
        return $this->registry->registry('current_product');
    }

    /**
     * Get product ID
     *
     * @return int|null
     */
    public function getProductId()
    {
        $product = $this->getProduct();
        return $product ? $product->getId() : null;
    }

    /**
     * Get product price
     *
     * @return float
     */
    public function getProductPrice()
    {
        $product = $this->getProduct();
        return $product ? $this->priceResolver->getPrice($product) : 0;
    }

    /**
     * Get formatted product price
     *
     * @return string
     */
    public function getFormattedPrice()
    {
        return $this->pricingHelper->currency($this->getProductPrice(), true, false);
    }

    /**
     * Check if the product has a valid price to track.
     * A price drop alert makes no sense when the price is 0 or negative —
     * there's nothing to drop from.
     *
     * @return bool
     */
    public function hasValidPrice()
    {
        return $this->getProductPrice() > 0;
    }

    /**
     * Get customer email
     *
     * @return string|null
     */
    public function getCustomerEmail()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getEmail();
        }
        return null;
    }

    /**
     * Get customer name
     *
     * @return string|null
     */
    public function getCustomerName()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            return trim($customer->getFirstname() . ' ' . $customer->getLastname());
        }
        return null;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get subscribe URL
     *
     * @return string
     */
    public function getSubscribeUrl()
    {
        return $this->getUrl('pricedropalert/alert/price');
    }

    /**
     * Get unsubscribe URL
     *
     * @return string
     */
    public function getUnsubscribeUrl()
    {
        return $this->getUrl('pricedropalert/alert/unsubscribe');
    }

    /**
     * Get status check URL
     *
     * @return string
     */
    public function getStatusUrl()
    {
        return $this->getUrl('pricedropalert/alert/status');
    }

    /**
     * Get currency symbol
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    /**
     * Get helper
     *
     * @return PriceAlertHelper
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Get placement configuration (fixed position)
     *
     * @return string
     */
    public function getPlacement()
    {
        return 'after_price';
    }

    /**
     * Get placement CSS class
     *
     * @return string
     */
    public function getPlacementClass()
    {
        return 'price-alert-placement-after-price';
    }
}
