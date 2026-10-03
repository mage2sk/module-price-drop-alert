<?php
namespace Panth\PriceDropAlert\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PriceAlert extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('panth_price_alert', 'alert_id');
    }

    protected function _beforeSave(AbstractModel $object)
    {
        if ((string) $object->getData('unsubscribe_token') === '') {
            $object->setData('unsubscribe_token', bin2hex(random_bytes(16)));
        }
        return parent::_beforeSave($object);
    }
}
