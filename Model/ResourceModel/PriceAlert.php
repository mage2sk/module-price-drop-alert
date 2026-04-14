<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Price Alert Resource Model
 */

namespace Panth\PriceDropAlert\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PriceAlert extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('panth_price_alert', 'alert_id');
    }
}
