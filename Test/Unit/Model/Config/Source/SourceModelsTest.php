<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Model\Config\Source;

use Panth\PriceDropAlert\Model\Config\Source\DisplayPosition;
use Panth\PriceDropAlert\Model\Config\Source\Placement;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\Source\Status;
use PHPUnit\Framework\TestCase;

class SourceModelsTest extends TestCase
{
    public function testStatusOptionsCoverAllAlertStates(): void
    {
        $options = (new Status())->toOptionArray();

        $this->assertSame(
            [PriceAlert::STATUS_ACTIVE, PriceAlert::STATUS_SENT, PriceAlert::STATUS_CANCELLED],
            array_column($options, 'value')
        );
        $this->assertSame('Cancelled', (string) $options[2]['label']);
    }

    public function testPlacementOptionArrayAndArrayAgree(): void
    {
        $source = new Placement();
        $options = $source->toOptionArray();
        $map = $source->toArray();

        $this->assertCount(5, $options);
        $this->assertSame(array_keys($map), array_column($options, 'value'));
        foreach ($options as $option) {
            $this->assertSame((string) $map[$option['value']], (string) $option['label']);
        }
    }

    public function testPlacementContainerConfig(): void
    {
        $source = new Placement();

        $this->assertSame(
            ['container' => 'product.info.main', 'position' => 'before', 'sibling' => 'product.info.addtocart'],
            $source->getContainerConfig(Placement::ABOVE_ADD_TO_CART)
        );
        $this->assertSame('product.info.addtocart.additional', $source->getContainerConfig(Placement::BELOW_ADD_TO_CART)['sibling']);
        $this->assertSame('product.info.overview', $source->getContainerConfig(Placement::ABOVE_DESCRIPTION)['sibling']);
        $this->assertSame('product.info.details', $source->getContainerConfig(Placement::BELOW_DESCRIPTION)['sibling']);
        $this->assertSame(
            $source->getContainerConfig(Placement::AFTER_PRICE),
            $source->getContainerConfig('unknown')
        );
        $this->assertSame('product.info.price', $source->getContainerConfig('unknown')['sibling']);
    }

    public function testDisplayPositionOptionArrayAndArrayAgree(): void
    {
        $source = new DisplayPosition();
        $options = $source->toOptionArray();

        $this->assertCount(6, $options);
        $this->assertSame(array_keys($source->toArray()), array_column($options, 'value'));
    }

    public function testDisplayPositionContainerConfig(): void
    {
        $source = new DisplayPosition();

        $this->assertSame(
            ['container' => 'custom', 'position' => 'custom', 'sibling' => null],
            $source->getContainerConfig(DisplayPosition::CUSTOM_POSITION)
        );
        $this->assertSame('before', $source->getContainerConfig(DisplayPosition::ABOVE_ADD_TO_CART)['position']);
        $this->assertSame('after', $source->getContainerConfig(DisplayPosition::BELOW_ADD_TO_CART)['position']);
        $this->assertSame('product.info.overview', $source->getContainerConfig(DisplayPosition::ABOVE_DESCRIPTION)['sibling']);
        $this->assertSame('product.info.details', $source->getContainerConfig(DisplayPosition::BELOW_DESCRIPTION)['sibling']);
        $this->assertSame(
            $source->getContainerConfig(DisplayPosition::IN_PRODUCT_INFO_COLUMN),
            $source->getContainerConfig('nope')
        );
    }
}
