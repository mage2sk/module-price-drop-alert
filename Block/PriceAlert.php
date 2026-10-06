<?php
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

    public function isEnabled()
    {
        return $this->helper->isPriceAlertEnabled();
    }

    public function getProduct()
    {
        if ($this->hasData('product')) {
            return $this->getData('product');
        }
        return $this->registry->registry('current_product');
    }

    public function getProductId()
    {
        $product = $this->getProduct();
        return $product ? $product->getId() : null;
    }

    public function getProductPrice()
    {
        $product = $this->getProduct();
        return $product ? $this->priceResolver->getPrice($product) : 0;
    }

    public function getDisplayPrice()
    {
        return round((float) $this->pricingHelper->currency($this->getProductPrice(), false, false), 2);
    }

    public function getFormattedPrice()
    {
        return $this->pricingHelper->currency($this->getProductPrice(), true, false);
    }

    public function hasValidPrice()
    {
        return $this->getProductPrice() > 0;
    }

    public function getCustomerEmail()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getEmail();
        }
        return null;
    }

    public function getCustomerName()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            return trim($customer->getFirstname() . ' ' . $customer->getLastname());
        }
        return null;
    }

    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getSubscribeUrl()
    {
        return $this->getUrl('pricedropalert/alert/price');
    }

    public function getUnsubscribeUrl()
    {
        return $this->getUrl('pricedropalert/alert/unsubscribe');
    }

    public function getStatusUrl()
    {
        return $this->getUrl('pricedropalert/alert/status');
    }

    public function getCurrencySymbol()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    public function getHelper()
    {
        return $this->helper;
    }

    public function isCompact(): bool
    {
        return $this->helper->isCompactStyle();
    }

    public function getPlacement()
    {
        return 'after_price';
    }

    public function getPlacementClass()
    {
        return 'price-alert-placement-after-price';
    }
}
