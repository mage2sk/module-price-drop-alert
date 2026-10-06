<?php
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

    public function getAlert()
    {
        return $this->coreRegistry->registry('pricedropalert_alert');
    }

    public function getProduct()
    {
        try {
            return $this->productRepository->getById($this->getAlert()->getProductId());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getCustomerEmail()
    {
        $alert = $this->getAlert();

        if ($alert->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($alert->getCustomerId());
                return $customer->getEmail();
            } catch (\Exception $e) {
            }
        }

        return $alert->getEmail() ?: __('N/A');
    }

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

    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['alert_id' => $this->getAlert()->getId()]);
    }

    public function getSendEmailUrl()
    {
        return $this->getUrl('*/*/send', ['alert_id' => $this->getAlert()->getId()]);
    }

    public function formatPrice($price)
    {
        return $this->priceCurrency->format($price, false);
    }

    public function getCurrentProductPrice()
    {
        $product = $this->getProduct();
        if (!$product) {
            return 0;
        }
        $alert = $this->getAlert();
        if ($alert && $alert->getId()) {
            $price = $this->priceResolver->getPriceForAlert($alert);
            if ($price > 0) {
                return $price;
            }
        }
        return $this->priceResolver->getPrice($product);
    }
}
