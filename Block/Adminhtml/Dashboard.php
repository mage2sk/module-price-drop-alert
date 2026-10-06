<?php
namespace Panth\PriceDropAlert\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Panth\PriceDropAlert\Model\PriceAlert;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Dashboard extends Template
{
    protected $collectionFactory;
    protected $priceCurrency;
    private ProductRepositoryInterface $productRepository;
    private array $productNameCache = [];

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        PriceCurrencyInterface $priceCurrency,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->priceCurrency = $priceCurrency;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    public function getProductName(int $productId): string
    {
        if (!isset($this->productNameCache[$productId])) {
            try {
                $this->productNameCache[$productId] = $this->productRepository->getById($productId)->getName();
            } catch (\Exception $e) {
                $this->productNameCache[$productId] = 'Product #' . $productId;
            }
        }
        return $this->productNameCache[$productId];
    }

    public function getTotalAlertsCount()
    {
        return $this->collectionFactory->create()->getSize();
    }

    public function getActiveAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_ACTIVE)
            ->getSize();
    }

    public function getSentAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_SENT)
            ->getSize();
    }

    public function getCancelledAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_CANCELLED)
            ->getSize();
    }

    public function getRecentAlerts($limit = 10)
    {
        return $this->collectionFactory->create()
            ->setOrder('created_at', 'DESC')
            ->setPageSize($limit);
    }

    public function getRecentSentAlerts($limit = 10)
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_SENT)
            ->setOrder('sent_at', 'DESC')
            ->setPageSize($limit);
    }

    public function getManageAlertsUrl()
    {
        return $this->getUrl('pricedropalert/alert/index');
    }

    public function getViewAlertUrl($alertId)
    {
        return $this->getUrl('pricedropalert/alert/view', ['alert_id' => $alertId]);
    }

    public function formatPrice($price)
    {
        return $this->priceCurrency->format($price, false);
    }

    public function getMostWantedProducts($limit = 10)
    {
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $collection->getMainTable()],
                [
                    'product_id',
                    'alert_count' => new \Magento\Framework\DB\Sql\Expression('COUNT(*)')
                ]
            )
            ->where('status = ?', PriceAlert::STATUS_ACTIVE)
            ->group('product_id')
            ->order('alert_count DESC')
            ->limit($limit);

        return $connection->fetchAll($select);
    }

    public function getAlertTrendData()
    {
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $collection->getMainTable()],
                [
                    'date' => new \Magento\Framework\DB\Sql\Expression('DATE(created_at)'),
                    'count' => new \Magento\Framework\DB\Sql\Expression('COUNT(*)')
                ]
            )
            ->where('created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)')
            ->group('DATE(created_at)')
            ->order('date ASC');

        $counts = [];
        foreach ($connection->fetchAll($select) as $row) {
            $counts[(string)$row['date']] = (int)$row['count'];
        }
        $today = new \DateTimeImmutable((string)$connection->fetchOne('SELECT CURDATE()'));
        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->modify('-' . $i . ' day')->format('Y-m-d');
            $result[] = ['date' => $day, 'count' => $counts[$day] ?? 0];
        }
        return $result;
    }

    public function getAverageTargetPrice()
    {
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $collection->getMainTable()],
                [
                    'avg_price' => new \Magento\Framework\DB\Sql\Expression('AVG(target_price)')
                ]
            )
            ->where('status = ?', PriceAlert::STATUS_ACTIVE);

        $result = $connection->fetchOne($select);
        return $result ? (float)$result : 0;
    }

    public function getTodayAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('created_at', ['gteq' => date('Y-m-d 00:00:00')])
            ->getSize();
    }

    public function getTodaySentCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_SENT)
            ->addFieldToFilter('sent_at', ['gteq' => date('Y-m-d 00:00:00')])
            ->getSize();
    }
}
