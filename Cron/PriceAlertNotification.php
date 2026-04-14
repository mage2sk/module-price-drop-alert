<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 *
 * Cron job that checks all active price alerts and sends notifications
 * when the current product price drops to or below the target.
 *
 * Uses PriceResolver for smart handling of all product types:
 * simple, configurable, bundle, grouped, virtual, downloadable.
 */
declare(strict_types=1);

namespace Panth\PriceDropAlert\Cron;

use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Model\EmailSender;
use Psr\Log\LoggerInterface;

class PriceAlertNotification
{
    private CollectionFactory $collectionFactory;
    private PriceResolver $priceResolver;
    private EmailSender $emailSender;
    private LoggerInterface $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        PriceResolver $priceResolver,
        EmailSender $emailSender,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->priceResolver = $priceResolver;
        $this->emailSender = $emailSender;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_ACTIVE);

        $sent = 0;
        $checked = 0;

        foreach ($collection as $alert) {
            $checked++;
            try {
                $currentPrice = $this->priceResolver->getPriceById((int) $alert->getProductId());

                if ($currentPrice <= 0) {
                    $this->logger->warning('PriceDropAlert Cron: Could not resolve price for product #' . $alert->getProductId());
                    continue;
                }

                $subscribedPrice = (float) $alert->getSubscribedPrice();
                $targetPrice = $alert->getTargetPrice() ? (float) $alert->getTargetPrice() : null;

                // Determine if we should notify:
                // 1. If target price is set: notify when current price <= target
                // 2. If no target: notify when current price < subscribed price (any drop)
                $shouldNotify = false;
                if ($targetPrice !== null && $targetPrice > 0) {
                    $shouldNotify = $currentPrice <= $targetPrice;
                } else {
                    $shouldNotify = $subscribedPrice > 0 && $currentPrice < $subscribedPrice;
                }

                if ($shouldNotify) {
                    $this->emailSender->sendAlertEmail($alert);
                    $sent++;
                    $this->logger->info(sprintf(
                        'PriceDropAlert Cron: Sent alert for product #%s (was $%.2f, now $%.2f) to %s',
                        $alert->getProductId(),
                        $subscribedPrice,
                        $currentPrice,
                        $alert->getEmail()
                    ));
                }
            } catch (\Exception $e) {
                $this->logger->error('PriceDropAlert Cron: Error processing alert #' . $alert->getId() . ': ' . $e->getMessage());
            }
        }

        $this->logger->info(sprintf('PriceDropAlert Cron: Checked %d alerts, sent %d notifications', $checked, $sent));
    }
}
