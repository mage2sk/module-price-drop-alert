<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Grid;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    private const KEYWORD_COLUMNS = ['main_table.email', 'main_table.customer_name'];

    private MetadataPool $metadataPool;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'panth_price_alert',
        $resourceModel = \Panth\PriceDropAlert\Model\ResourceModel\PriceAlert::class,
        $identifierName = null,
        $connectionName = null,
        ?MetadataPool $metadataPool = null
    ) {
        $this->metadataPool = $metadataPool ?? ObjectManager::getInstance()->get(MetadataPool::class);
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'product_name') {
            $this->getSelect()->where(
                'main_table.product_id IN (' . $this->getProductNameSelect($condition)->assemble() . ')'
            );
            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }

    public function applyKeywordSearch(string $keyword): self
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return $this;
        }

        $connection = $this->getConnection();
        $like = '%' . addcslashes($keyword, '\\%_') . '%';
        $parts = [];
        foreach (self::KEYWORD_COLUMNS as $column) {
            $parts[] = $connection->quoteInto($column . ' LIKE ?', $like);
        }
        $parts[] = 'main_table.product_id IN (' . $this->getProductNameSelect(['like' => $like])->assemble() . ')';
        if (ctype_digit($keyword)) {
            $parts[] = $connection->quoteInto('main_table.product_id = ?', (int) $keyword);
        }
        $this->getSelect()->where(implode(' OR ', $parts));

        return $this;
    }

    private function getProductNameSelect($condition): Select
    {
        $connection = $this->getConnection();
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $attributeId = (int) $connection->fetchOne(
            $connection->select()
                ->from(['ea' => $this->getTable('eav_attribute')], ['attribute_id'])
                ->join(['et' => $this->getTable('eav_entity_type')], 'et.entity_type_id = ea.entity_type_id', [])
                ->where('et.entity_type_code = ?', 'catalog_product')
                ->where('ea.attribute_code = ?', 'name')
        );

        return $connection->select()
            ->distinct(true)
            ->from(['cpe' => $this->getTable('catalog_product_entity')], ['entity_id'])
            ->join(
                ['cpv' => $this->getTable('catalog_product_entity_varchar')],
                sprintf('cpv.%1$s = cpe.%1$s AND cpv.attribute_id = %2$d', $linkField, $attributeId),
                []
            )
            ->where($connection->prepareSqlCondition('cpv.value', $condition));
    }
}
