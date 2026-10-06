<?php
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

        $product = $observer->getEvent()->getProduct();
        if (!$product || !$product->getId()) {
            return;
        }

        $productId = (int) $product->getId();

        $priceChanged = $product->dataHasChangedFor('price')
            || $product->dataHasChangedFor('special_price')
            || $product->dataHasChangedFor('special_from_date')
            || $product->dataHasChangedFor('special_to_date');

        if (!$priceChanged) {
            return;
        }

        $productIdsToCheck = [$productId];

        if ($product->getTypeId() === 'simple') {
            try {
                $parentIds = $this->configurableResource->getParentIdsByChild($productId);
                foreach ($parentIds as $parentId) {
                    $productIdsToCheck[] = (int) $parentId;
                }
            } catch (\Exception $e) {
            }
        }

        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('product_id', ['in' => $productIdsToCheck])
            ->addFieldToFilter('status', PriceAlert::STATUS_ACTIVE);

        if ($collection->getSize() === 0) {
            return;
        }

        $sent = 0;
        foreach ($collection as $alert) {
            try {
                $alertStoreId = (int) $alert->getStoreId();
                if (!$this->helper->isEnabled($alertStoreId)) {
                    continue;
                }
                $currentPrice = $this->priceResolver->getPriceForAlert($alert);
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
