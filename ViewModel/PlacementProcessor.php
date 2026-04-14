<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * ViewModel for Processing Block Placement
 */

namespace Panth\PriceDropAlert\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Panth\PriceDropAlert\Model\Config\Source\Placement;

class PlacementProcessor implements ArgumentInterface
{
    /**
     * @var PriceAlertHelper
     */
    protected $helper;

    /**
     * @var Placement
     */
    protected $placementSource;

    /**
     * @param PriceAlertHelper $helper
     * @param Placement $placementSource
     */
    public function __construct(
        PriceAlertHelper $helper,
        Placement $placementSource
    ) {
        $this->helper = $helper;
        $this->placementSource = $placementSource;
    }

    /**
     * Get current placement setting
     *
     * @return string
     */
    public function getPlacement()
    {
        return $this->helper->getPlacement();
    }

    /**
     * Get container configuration
     *
     * @return array
     */
    public function getContainerConfig()
    {
        $placement = $this->getPlacement();
        return $this->placementSource->getContainerConfig($placement);
    }

    /**
     * Get placement CSS class
     *
     * @return string
     */
    public function getPlacementClass()
    {
        $placement = $this->getPlacement();
        return 'price-alert-placement-' . str_replace('_', '-', $placement);
    }

    /**
     * Check if placement matches given value
     *
     * @param string $placementValue
     * @return bool
     */
    public function isPlacement($placementValue)
    {
        return $this->getPlacement() === $placementValue;
    }
}
