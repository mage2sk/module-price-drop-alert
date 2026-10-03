<?php
namespace Panth\PriceDropAlert\Model\ResourceModel\PriceAlert;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'alert_id';

    protected function _construct()
    {
        $this->_init(
            \Panth\PriceDropAlert\Model\PriceAlert::class,
            \Panth\PriceDropAlert\Model\ResourceModel\PriceAlert::class
        );
    }
}
