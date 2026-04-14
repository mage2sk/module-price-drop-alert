<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Customer Name Column
 */

namespace Panth\PriceDropAlert\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerName extends Column
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomerRepositoryInterface $customerRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->customerRepository = $customerRepository;
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
                // Check if customer_name exists in row data and is not empty
                if (isset($item['customer_name']) && !empty($item['customer_name'])) {
                    $item['customer_name'] = $item['customer_name'];
                } elseif (isset($item['customer_id']) && $item['customer_id']) {
                    // Fetch from CustomerRepositoryInterface if customer_id exists
                    try {
                        $customer = $this->customerRepository->getById($item['customer_id']);
                        $item['customer_name'] = $customer->getFirstname() . ' ' . $customer->getLastname();
                    } catch (\Exception $e) {
                        $item['customer_name'] = __('N/A');
                    }
                } else {
                    // Return "Guest" for guest customers
                    $item['customer_name'] = __('Guest');
                }
            }
        }

        return $dataSource;
    }
}
