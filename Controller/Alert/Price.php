<?php
namespace Panth\PriceDropAlert\Controller\Alert;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Store\Model\StoreManagerInterface;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Model\RateLimiter;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class Price extends Action implements HttpPostActionInterface
{
    public const SESSION_ALERT_IDS = 'pricedropalert_alert_ids';

    protected $resultJsonFactory;

    protected $customerSession;

    protected $storeManager;

    protected $priceAlertFactory;

    protected $helper;

    protected $productRepository;

    private PriceResolver $priceResolver;

    private LoggerInterface $logger;

    private RateLimiter $rateLimiter;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        PriceAlertFactory $priceAlertFactory,
        PriceAlertHelper $helper,
        ProductRepositoryInterface $productRepository,
        PriceResolver $priceResolver,
        LoggerInterface $logger,
        RateLimiter $rateLimiter
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->priceAlertFactory = $priceAlertFactory;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->priceResolver = $priceResolver;
        $this->logger = $logger;
        $this->rateLimiter = $rateLimiter;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->helper->isPriceAlertEnabled()) {
            return $result->setData([
                'error' => true,
                'message' => __('Price alerts are disabled.')
            ]);
        }

        if (!$this->rateLimiter->isAllowed('subscribe')) {
            $result->setHttpResponseCode(429);
            return $result->setData([
                'error' => true,
                'message' => __('Too many requests. Please try again later.')
            ]);
        }

        $productId = (int) $this->getRequest()->getParam('product_id');
        $email = trim((string) $this->getRequest()->getParam('email'));
        $customerName = (string) $this->getRequest()->getParam('customer_name');
        $triggerPrice = $this->getRequest()->getParam('trigger_price');
        if ($triggerPrice === null || $triggerPrice === '') {
            $triggerPrice = $this->getRequest()->getParam('target_price');
        }

        if ($productId <= 0) {
            return $result->setData([
                'error' => true,
                'message' => __('Product ID is required.')
            ]);
        }

        $customerId = $this->customerSession->isLoggedIn()
            ? (int) $this->customerSession->getCustomerId()
            : null;

        if (!$customerId && !$this->helper->isGuestAllowed()) {
            return $result->setData([
                'error' => true,
                'message' => __('Please sign in to subscribe to price alerts.')
            ]);
        }

        if (!$customerId && $email === '') {
            return $result->setData([
                'error' => true,
                'message' => __('Email is required for guests.')
            ]);
        }

        $customerName = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', strip_tags($customerName)));

        if (!$customerId && $customerName === '') {
            return $result->setData([
                'error' => true,
                'message' => __('Name is required for guests.')
            ]);
        }

        if (mb_strlen($customerName) > 255) {
            return $result->setData([
                'error' => true,
                'message' => __('Name is too long. Maximum 255 characters allowed.')
            ]);
        }

        if (!$customerId && (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            return $result->setData([
                'error' => true,
                'message' => __('Please enter a valid email address.')
            ]);
        }

        if ($customerId) {
            $customer = $this->customerSession->getCustomer();
            $email = (string) $customer->getEmail();
            $customerName = trim($customer->getFirstname() . ' ' . $customer->getLastname());
        }

        try {
            $store = $this->storeManager->getStore();
            $product = $this->productRepository->getById($productId, false, (int) $store->getId());

            if ((int) $product->getStatus() !== ProductStatus::STATUS_ENABLED
                || !$product->isVisibleInSiteVisibility()
                || !in_array((int) $store->getWebsiteId(), array_map('intval', (array) $product->getWebsiteIds()), true)
            ) {
                return $result->setData([
                    'error' => true,
                    'message' => __('This product is not available.')
                ]);
            }

            $currentPrice = $this->priceResolver->getPrice($product);
            if ($currentPrice <= 0) {
                return $result->setData([
                    'error' => true,
                    'message' => __('This product is not available.')
                ]);
            }

            $displayRate = 1.0;
            $displayCode = (string) $store->getCurrentCurrencyCode();
            if ($displayCode !== '' && $displayCode !== (string) $store->getBaseCurrencyCode()) {
                $displayRate = (float) $store->getBaseCurrency()->getRate($displayCode);
                if ($displayRate <= 0) {
                    $displayRate = 1.0;
                }
            }

            $displayTriggerPrice = null;
            if ($triggerPrice !== null && $triggerPrice !== '') {
                $displayTriggerPrice = (float) $triggerPrice;
                $triggerPrice = round($displayTriggerPrice / $displayRate, 4);
                if ($displayTriggerPrice <= 0 || $triggerPrice <= 0) {
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

            $successMessage = $triggerPrice
                ? __(
                    'You will be notified when the price drops to %1 or below.',
                    $store->getCurrentCurrency()->format($displayTriggerPrice, [], false)
                )
                : __('You will be notified when the price drops for this product.');

            $collection = $this->priceAlertFactory->create()->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('status', \Panth\PriceDropAlert\Model\PriceAlert::STATUS_ACTIVE);

            if ($collection->getSize() > 0) {
                if (!$customerId) {
                    return $result->setData([
                        'success' => true,
                        'message' => $successMessage
                    ]);
                }
                return $result->setData([
                    'error' => true,
                    'message' => __('You are already subscribed to price alerts for this product.')
                ]);
            }

            $priceAlert = $this->priceAlertFactory->create();
            $priceAlert->setCustomerId($customerId)
                ->setProductId($productId)
                ->setSubscribedPrice($currentPrice)
                ->setTriggerPrice($triggerPrice)
                ->setEmail($email)
                ->setCustomerName($customerName)
                ->setStoreId($store->getId())
                ->setStatus(\Panth\PriceDropAlert\Model\PriceAlert::STATUS_ACTIVE)
                ->save();

            if (!$customerId) {
                $alertIds = (array) $this->customerSession->getData(self::SESSION_ALERT_IDS);
                $alertIds[] = (int) $priceAlert->getId();
                $this->customerSession->setData(self::SESSION_ALERT_IDS, array_values(array_unique($alertIds)));
            }

            return $result->setData([
                'success' => true,
                'message' => $successMessage
            ]);
        } catch (NoSuchEntityException $e) {
            return $result->setData([
                'error' => true,
                'message' => __('This product is not available.')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('PriceDropAlert: subscribe failed: ' . $e->getMessage());
            return $result->setData([
                'error' => true,
                'message' => __('Unable to save the price alert. Please try again later.')
            ]);
        }
    }
}
