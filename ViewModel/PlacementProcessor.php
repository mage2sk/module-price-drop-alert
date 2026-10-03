<?php
namespace Panth\PriceDropAlert\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Panth\PriceDropAlert\Model\Config\Source\Placement;

class PlacementProcessor implements ArgumentInterface
{
    protected $helper;

    protected $placementSource;

    public function __construct(
        PriceAlertHelper $helper,
        Placement $placementSource
    ) {
        $this->helper = $helper;
        $this->placementSource = $placementSource;
    }

    public function getPlacement()
    {
        return Placement::AFTER_PRICE;
    }

    public function getContainerConfig()
    {
        $placement = $this->getPlacement();
        return $this->placementSource->getContainerConfig($placement);
    }

    public function getPlacementClass()
    {
        $placement = $this->getPlacement();
        return 'price-alert-placement-' . str_replace('_', '-', $placement);
    }

    public function isPlacement($placementValue)
    {
        return $this->getPlacement() === $placementValue;
    }
}
