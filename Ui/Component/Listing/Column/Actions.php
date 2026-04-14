<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Actions Column
 */

namespace Panth\PriceDropAlert\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['alert_id'])) {
                    $item[$name]['view'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'pricedropalert/alert/view',
                            ['alert_id' => $item['alert_id']]
                        ),
                        'label' => __('View')
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'pricedropalert/alert/delete',
                            ['alert_id' => $item['alert_id']]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete Alert'),
                            'message' => __('Are you sure you want to delete this alert?')
                        ]
                    ];
                    $item[$name]['send'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'pricedropalert/alert/send',
                            ['alert_id' => $item['alert_id']]
                        ),
                        'label' => __('Send Email'),
                        'confirm' => [
                            'title' => __('Send Email'),
                            'message' => __('Are you sure you want to send email for this alert?')
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
