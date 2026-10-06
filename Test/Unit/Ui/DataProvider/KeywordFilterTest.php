<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Grid\Collection as GridCollection;
use Panth\PriceDropAlert\Ui\DataProvider\KeywordFilter;
use PHPUnit\Framework\TestCase;

class KeywordFilterTest extends TestCase
{
    public function testAppliesKeywordSearchToGridCollection(): void
    {
        $filter = $this->createStub(Filter::class);
        $filter->method('getValue')->willReturn('jane');
        $collection = $this->createMock(GridCollection::class);
        $collection->expects($this->once())->method('applyKeywordSearch')->with('jane')->willReturnSelf();

        (new KeywordFilter())->apply($collection, $filter);
    }

    public function testIgnoresOtherCollections(): void
    {
        $filter = $this->createMock(Filter::class);
        $filter->expects($this->never())->method('getValue');

        (new KeywordFilter())->apply($this->createStub(Collection::class), $filter);
    }
}
