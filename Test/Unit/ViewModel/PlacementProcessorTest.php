<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\ViewModel;

use Panth\PriceDropAlert\Helper\Data;
use Panth\PriceDropAlert\Model\Config\Source\Placement;
use Panth\PriceDropAlert\ViewModel\PlacementProcessor;
use PHPUnit\Framework\TestCase;

class PlacementProcessorTest extends TestCase
{
    private function processor(): PlacementProcessor
    {
        return new PlacementProcessor($this->createStub(Data::class), new Placement());
    }

    public function testPlacementIsFixedAfterPrice(): void
    {
        $processor = $this->processor();

        $this->assertSame(Placement::AFTER_PRICE, $processor->getPlacement());
        $this->assertTrue($processor->isPlacement(Placement::AFTER_PRICE));
        $this->assertFalse($processor->isPlacement(Placement::BELOW_DESCRIPTION));
    }

    public function testPlacementClassUsesDashes(): void
    {
        $this->assertSame('price-alert-placement-after-price', $this->processor()->getPlacementClass());
    }

    public function testContainerConfigComesFromSource(): void
    {
        $this->assertSame(
            ['container' => 'product.info.main', 'position' => 'after', 'sibling' => 'product.info.price'],
            $this->processor()->getContainerConfig()
        );
    }
}
