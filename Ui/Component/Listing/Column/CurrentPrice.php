<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Current Price Column — uses PriceResolver for all product types
 */
declare(strict_types=1);

namespace Panth\PriceDropAlert\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Panth\PriceDropAlert\Model\PriceResolver;

class CurrentPrice extends Column
{
    private PriceResolver $priceResolver;
    private PriceCurrencyInterface $priceCurrency;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PriceResolver $priceResolver,
        PriceCurrencyInterface $priceCurrency,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->priceResolver = $priceResolver;
        $this->priceCurrency = $priceCurrency;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['product_id'])) {
                    try {
                        $price = $this->priceResolver->getPriceById((int) $item['product_id']);
                        $item['current_price'] = $this->priceCurrency->format($price, false);
                    } catch (\Exception $e) {
                        $item['current_price'] = __('N/A');
                    }
                } else {
                    $item['current_price'] = __('N/A');
                }
            }
        }

        return $dataSource;
    }
}
