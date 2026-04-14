<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Price Alert Collection
 */

namespace Panth\PriceDropAlert\Model\ResourceModel\PriceAlert;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'alert_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Panth\PriceDropAlert\Model\PriceAlert::class,
            \Panth\PriceDropAlert\Model\ResourceModel\PriceAlert::class
        );
    }
}
