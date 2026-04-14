<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Dashboard Block
 */

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

    /**
     * Get total alerts count
     *
     * @return int
     */
    public function getTotalAlertsCount()
    {
        return $this->collectionFactory->create()->getSize();
    }

    /**
     * Get active alerts count
     *
     * @return int
     */
    public function getActiveAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_ACTIVE)
            ->getSize();
    }

    /**
     * Get sent alerts count
     *
     * @return int
     */
    public function getSentAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_SENT)
            ->getSize();
    }

    /**
     * Get cancelled alerts count
     *
     * @return int
     */
    public function getCancelledAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_CANCELLED)
            ->getSize();
    }

    /**
     * Get recent alerts
     *
     * @param int $limit
     * @return \Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection
     */
    public function getRecentAlerts($limit = 10)
    {
        return $this->collectionFactory->create()
            ->setOrder('created_at', 'DESC')
            ->setPageSize($limit);
    }

    /**
     * Get recent sent alerts
     *
     * @param int $limit
     * @return \Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection
     */
    public function getRecentSentAlerts($limit = 10)
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_SENT)
            ->setOrder('sent_at', 'DESC')
            ->setPageSize($limit);
    }

    /**
     * Get manage alerts URL
     *
     * @return string
     */
    public function getManageAlertsUrl()
    {
        return $this->getUrl('pricedropalert/alert/index');
    }

    /**
     * Get view alert URL
     *
     * @param int $alertId
     * @return string
     */
    public function getViewAlertUrl($alertId)
    {
        return $this->getUrl('pricedropalert/alert/view', ['alert_id' => $alertId]);
    }

    /**
     * Format price using store currency
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price)
    {
        return $this->priceCurrency->format($price, false);
    }

    /**
     * Get most wanted products (products with most alerts)
     *
     * @param int $limit
     * @return array
     */
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

    /**
     * Get alert trend data for the last 7 days
     *
     * @return array
     */
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
            ->where('created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
            ->group('DATE(created_at)')
            ->order('date ASC');

        return $connection->fetchAll($select);
    }

    /**
     * Get average target price
     *
     * @return float
     */
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

    /**
     * Get alerts created today
     *
     * @return int
     */
    public function getTodayAlertsCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('created_at', ['gteq' => date('Y-m-d 00:00:00')])
            ->getSize();
    }

    /**
     * Get alerts sent today
     *
     * @return int
     */
    public function getTodaySentCount()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('status', PriceAlert::STATUS_SENT)
            ->addFieldToFilter('sent_at', ['gteq' => date('Y-m-d 00:00:00')])
            ->getSize();
    }
}
