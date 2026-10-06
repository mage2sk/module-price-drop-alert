<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Url as FrontendUrl;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class EmailSender
{
    private ProductRepositoryInterface $productRepository;
    private TransportBuilder $transportBuilder;
    private StoreManagerInterface $storeManager;
    private ScopeConfigInterface $scopeConfig;
    private PriceResolver $priceResolver;
    private LoggerInterface $logger;
    private PriceCurrencyInterface $priceCurrency;

    private FrontendUrl $frontendUrl;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        PriceResolver $priceResolver,
        LoggerInterface $logger,
        PriceCurrencyInterface $priceCurrency,
        ?FrontendUrl $frontendUrl = null
    ) {
        $this->productRepository = $productRepository;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->priceResolver = $priceResolver;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
        $this->frontendUrl = $frontendUrl ?? ObjectManager::getInstance()->get(FrontendUrl::class);
    }

    public function sendAlertEmail(PriceAlert $alert): bool
    {
        $store = $this->storeManager->getStore($alert->getStoreId());
        $storeId = (int) $store->getId();
        $product = $this->productRepository->getById((int) $alert->getProductId(), false, $storeId);
        $currentPrice = $this->priceResolver->getPriceForAlert($alert);
        if ($currentPrice <= 0) {
            $currentPrice = $this->priceResolver->getPrice($product);
        }
        $subscribedPrice = (float) $alert->getSubscribedPrice();
        $oldPrice = $subscribedPrice > 0 ? $subscribedPrice : $currentPrice;

        $discount = $oldPrice > 0 ? round((($oldPrice - $currentPrice) / $oldPrice) * 100, 1) : 0;
        if ($discount < 0) {
            $discount = 0;
        }

        $emailSender = $this->scopeConfig->getValue(
            'pricedropalert/email/sender', ScopeInterface::SCOPE_STORE, $storeId
        ) ?: 'general';

        $emailTemplate = $this->scopeConfig->getValue(
            'pricedropalert/email/email_template', ScopeInterface::SCOPE_STORE, $storeId
        ) ?: 'pricedropalert_email_email_template';

        $currency = $store->getDefaultCurrency();
        $customerName = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $alert->getCustomerName()));

        if ((string) $alert->getData('unsubscribe_token') === '') {
            $alert->save();
        }
        $unsubscribeUrl = $this->frontendUrl->setScope($store->getId())->getUrl('pricedropalert/unsubscribe/index', [
            'id' => (int) $alert->getId(),
            'token' => (string) $alert->getData('unsubscribe_token'),
            '_nosid' => true,
        ]);

        $templateVars = [
            'customer_name' => $customerName !== '' ? $customerName : (string) __('Valued Customer'),
            'product_name'  => $product->getName(),
            'product_url'   => $product->getProductUrl(),
            'product_price' => number_format($currentPrice, 2),
            'old_price'     => number_format($oldPrice, 2),
            'new_price'     => number_format($currentPrice, 2),
            'old_price_formatted' => $this->priceCurrency->convertAndFormat($oldPrice, false, 2, $store, $currency),
            'new_price_formatted' => $this->priceCurrency->convertAndFormat($currentPrice, false, 2, $store, $currency),
            'discount_percent' => number_format($discount, 0),
            'has_discount' => round($discount) >= 1 ? '1' : '',
            'unsubscribe_url' => $unsubscribeUrl,
            'store'         => $store,
        ];

        $this->logger->info('PriceDropAlert: Sending email', [
            'alert_id' => $alert->getId(),
            'product' => $product->getName() . ' (' . $product->getTypeId() . ')',
            'old_price' => $oldPrice,
            'new_price' => $currentPrice,
        ]);

        $transport = $this->transportBuilder
            ->setTemplateIdentifier($emailTemplate)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
            ->setTemplateVars($templateVars)
            ->setFromByScope($emailSender, $storeId)
            ->addTo($alert->getEmail(), $customerName)
            ->getTransport();

        $transport->sendMessage();

        if ((int) $alert->getStatus() !== PriceAlert::STATUS_SENT) {
            $alert->setStatus(PriceAlert::STATUS_SENT);
            $alert->setSentAt(date('Y-m-d H:i:s'));
            $alert->save();
        }

        $this->logger->info('PriceDropAlert: Email sent for alert #' . $alert->getId());
        return true;
    }
}
