<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Cron;

use Magento\Framework\FlagManager;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Model\EmailSender;
use Psr\Log\LoggerInterface;

class PriceAlertNotification
{
    private const BATCH_SIZE = 500;
    private const FLAG_LAST_RUN = 'pricedropalert_last_run';

    private CollectionFactory $collectionFactory;
    private PriceResolver $priceResolver;
    private EmailSender $emailSender;
    private LoggerInterface $logger;
    private PriceAlertHelper $helper;
    private FlagManager $flagManager;

    public function __construct(
        CollectionFactory $collectionFactory,
        PriceResolver $priceResolver,
        EmailSender $emailSender,
        LoggerInterface $logger,
        PriceAlertHelper $helper,
        FlagManager $flagManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->priceResolver = $priceResolver;
        $this->emailSender = $emailSender;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->flagManager = $flagManager;
    }

    public function execute(): void
    {
        $now = time();
        $frequencyHours = max(1, $this->helper->getCronFrequency());
        $lastRun = (int) $this->flagManager->getFlagData(self::FLAG_LAST_RUN);
        if ($lastRun > 0 && ($now - $lastRun) < ($frequencyHours * 3600 - 300)) {
            return;
        }
        $this->flagManager->saveFlag(self::FLAG_LAST_RUN, $now);

        $sent = 0;
        $checked = 0;
        $lastId = 0;
        $enabledByStore = [];

        do {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('status', PriceAlert::STATUS_ACTIVE)
                ->addFieldToFilter('alert_id', ['gt' => $lastId])
                ->setOrder('alert_id', 'ASC')
                ->setPageSize(self::BATCH_SIZE)
                ->setCurPage(1);

            $batchCount = 0;
            foreach ($collection as $alert) {
                $batchCount++;
                $lastId = (int) $alert->getId();
                $storeId = (int) $alert->getStoreId();

                if (!isset($enabledByStore[$storeId])) {
                    $enabledByStore[$storeId] = $this->helper->isEnabled($storeId);
                }
                if (!$enabledByStore[$storeId]) {
                    continue;
                }

                $checked++;
                try {
                    $currentPrice = $this->priceResolver->getPriceForAlert($alert);

                    if ($currentPrice <= 0) {
                        $this->logger->warning(
                            'PriceDropAlert Cron: Could not resolve price for product #' . $alert->getProductId()
                        );
                        continue;
                    }

                    $subscribedPrice = (float) $alert->getSubscribedPrice();
                    $targetPrice = $alert->getTargetPrice() ? (float) $alert->getTargetPrice() : null;

                    if ($targetPrice !== null && $targetPrice > 0) {
                        $shouldNotify = $currentPrice <= $targetPrice;
                    } else {
                        $shouldNotify = $subscribedPrice > 0 && $currentPrice < $subscribedPrice;
                    }

                    if ($shouldNotify) {
                        $this->emailSender->sendAlertEmail($alert);
                        $sent++;
                        $this->logger->info(sprintf(
                            'PriceDropAlert Cron: Sent alert #%d for product #%s (was %.2f, now %.2f)',
                            $alert->getId(),
                            $alert->getProductId(),
                            $subscribedPrice,
                            $currentPrice
                        ));
                    }
                } catch (\Exception $e) {
                    $this->logger->error(
                        'PriceDropAlert Cron: Error processing alert #' . $alert->getId() . ': ' . $e->getMessage()
                    );
                }
            }
            $collection->clear();
        } while ($batchCount === self::BATCH_SIZE);

        $this->logger->info(sprintf('PriceDropAlert Cron: Checked %d alerts, sent %d notifications', $checked, $sent));
    }
}
