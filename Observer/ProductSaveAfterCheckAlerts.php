<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 *
 * After a product is saved, check if its price dropped below any active
 * alert thresholds and send notifications immediately (not waiting for cron).
 *
 * This gives customers instant notifications when an admin changes a price.
 */
declare(strict_types=1);

namespace Panth\PriceDropAlert\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Model\EmailSender;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Psr\Log\LoggerInterface;

class ProductSaveAfterCheckAlerts implements ObserverInterface
{
    private CollectionFactory $collectionFactory;
    private PriceResolver $priceResolver;
    private EmailSender $emailSender;
    private PriceAlertHelper $helper;
    private ConfigurableResource $configurableResource;
    private LoggerInterface $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        PriceResolver $priceResolver,
        EmailSender $emailSender,
        PriceAlertHelper $helper,
        ConfigurableResource $configurableResource,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->priceResolver = $priceResolver;
        $this->emailSender = $emailSender;
        $this->helper = $helper;
        $this->configurableResource = $configurableResource;
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->helper->isEnabled()) {
            return;
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product || !$product->getId()) {
            return;
        }

        $productId = (int) $product->getId();

        // Check if price-related fields changed
        $priceChanged = $product->dataHasChangedFor('price')
            || $product->dataHasChangedFor('special_price')
            || $product->dataHasChangedFor('special_from_date')
            || $product->dataHasChangedFor('special_to_date');

        if (!$priceChanged) {
            return;
        }

        // Collect product IDs to check:
        // 1. The saved product itself
        // 2. If it's a simple child of configurable(s), also check the parent configurable IDs
        $productIdsToCheck = [$productId];

        if ($product->getTypeId() === 'simple') {
            try {
                $parentIds = $this->configurableResource->getParentIdsByChild($productId);
                foreach ($parentIds as $parentId) {
                    $productIdsToCheck[] = (int) $parentId;
                }
            } catch (\Exception $e) {
                // Ignore — module may not be installed
            }
        }

        // Get all active alerts for these product IDs
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('product_id', ['in' => $productIdsToCheck])
            ->addFieldToFilter('status', PriceAlert::STATUS_ACTIVE);

        if ($collection->getSize() === 0) {
            return;
        }

        $sent = 0;
        foreach ($collection as $alert) {
            try {
                $alertProductId = (int) $alert->getProductId();
                $currentPrice = $this->priceResolver->getPriceById($alertProductId);
                if ($currentPrice <= 0) {
                    continue;
                }

                $subscribedPrice = (float) $alert->getSubscribedPrice();
                $targetPrice = $alert->getTargetPrice() ? (float) $alert->getTargetPrice() : null;

                $shouldNotify = false;
                if ($targetPrice !== null && $targetPrice > 0) {
                    $shouldNotify = $currentPrice <= $targetPrice;
                } else {
                    $shouldNotify = $subscribedPrice > 0 && $currentPrice < $subscribedPrice;
                }

                if ($shouldNotify) {
                    $this->emailSender->sendAlertEmail($alert);
                    $sent++;
                }
            } catch (\Exception $e) {
                $this->logger->error('PriceDropAlert: Error sending immediate alert #' . $alert->getId() . ': ' . $e->getMessage());
            }
        }

        if ($sent > 0) {
            $this->logger->info(sprintf(
                'PriceDropAlert: Product #%d price changed, sent %d immediate notifications',
                $productId,
                $sent
            ));
        }
    }
}
