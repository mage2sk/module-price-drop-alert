<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Grid\Collection as GridCollection;

class KeywordFilter implements FilterApplierInterface
{
    public function apply(Collection $collection, Filter $filter)
    {
        if ($collection instanceof GridCollection) {
            $collection->applyKeywordSearch((string) $filter->getValue());
        }
    }
}
