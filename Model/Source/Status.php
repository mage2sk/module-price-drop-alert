<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Status Source Model
 */

namespace Panth\PriceDropAlert\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\PriceDropAlert\Model\PriceAlert;

class Status implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => PriceAlert::STATUS_ACTIVE, 'label' => __('Active/Pending')],
            ['value' => PriceAlert::STATUS_SENT, 'label' => __('Sent/Notified')],
            ['value' => PriceAlert::STATUS_CANCELLED, 'label' => __('Cancelled')]
        ];
    }
}
