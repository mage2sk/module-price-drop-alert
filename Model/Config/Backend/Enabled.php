<?php
/**
 * Backend Model for PriceDropAlert Enabled Field
 * License validation removed - standard Value backend
 *
 * @category  Panth
 * @package   Panth_PriceDropAlert
 */
declare(strict_types=1);

namespace Panth\PriceDropAlert\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class Enabled extends Value
{
    /**
     * @return $this
     */
    public function beforeSave()
    {
        return parent::beforeSave();
    }
}
