<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Observer to add placement layout handle for product pages
 */

namespace Panth\PriceDropAlert\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;

class AddPlacementLayoutHandle implements ObserverInterface
{
    /**
     * @var PriceAlertHelper
     */
    protected $helper;

    public function __construct(PriceAlertHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Add layout handle on product view pages when module is enabled
     */
    public function execute(Observer $observer)
    {
        $fullActionName = $observer->getData('full_action_name');
        if ($fullActionName !== 'catalog_product_view') {
            return;
        }

        if (!$this->helper->isEnabled()) {
            return;
        }

        $layout = $observer->getData('layout');
        $layout->getUpdate()->addHandle('pricedropalert_placement_after_price');
    }
}
