<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Email Sender Service — uses PriceResolver for all product types
 */
declare(strict_types=1);

namespace Panth\PriceDropAlert\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
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

    public function __construct(
        ProductRepositoryInterface $productRepository,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        PriceResolver $priceResolver,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->priceResolver = $priceResolver;
        $this->logger = $logger;
    }

    public function sendAlertEmail(PriceAlert $alert): bool
    {
        $product = $this->productRepository->getById($alert->getProductId());
        $currentPrice = $this->priceResolver->getPrice($product);
        $subscribedPrice = (float) $alert->getSubscribedPrice();
        $oldPrice = $subscribedPrice > 0 ? $subscribedPrice : $currentPrice;

        $discount = $oldPrice > 0 ? round((($oldPrice - $currentPrice) / $oldPrice) * 100, 1) : 0;
        if ($discount < 0) {
            $discount = 0;
        }

        $store = $this->storeManager->getStore($alert->getStoreId());
        $storeId = $store->getId();

        $emailSender = $this->scopeConfig->getValue(
            'pricedropalert/email/sender', ScopeInterface::SCOPE_STORE, $storeId
        ) ?: 'general';

        $emailTemplate = $this->scopeConfig->getValue(
            'pricedropalert/email/email_template', ScopeInterface::SCOPE_STORE, $storeId
        ) ?: 'pricedropalert_email_email_template';

        $templateVars = [
            'customer_name' => $alert->getCustomerName() ?: 'Valued Customer',
            'product_name'  => $product->getName(),
            'product_url'   => $product->getProductUrl(),
            'product_price' => number_format($currentPrice, 2),
            'old_price'     => number_format($oldPrice, 2),
            'new_price'     => number_format($currentPrice, 2),
            'discount_percent' => number_format($discount, 0),
            'store'         => $store,
        ];

        $this->logger->info('PriceDropAlert: Sending email', [
            'to' => $alert->getEmail(),
            'product' => $product->getName() . ' (' . $product->getTypeId() . ')',
            'old_price' => $oldPrice,
            'new_price' => $currentPrice,
        ]);

        $transport = $this->transportBuilder
            ->setTemplateIdentifier($emailTemplate)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
            ->setTemplateVars($templateVars)
            ->setFromByScope($emailSender, $storeId)
            ->addTo($alert->getEmail(), $alert->getCustomerName() ?: '')
            ->getTransport();

        $transport->sendMessage();

        if ((int) $alert->getStatus() !== PriceAlert::STATUS_SENT) {
            $alert->setStatus(PriceAlert::STATUS_SENT);
            $alert->setSentAt(date('Y-m-d H:i:s'));
            $alert->save();
        }

        $this->logger->info('PriceDropAlert: Email sent to ' . $alert->getEmail());
        return true;
    }
}
