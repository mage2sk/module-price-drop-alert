<?php
namespace Panth\PriceDropAlert\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerName extends Column
{
    protected $customerRepository;

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

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['customer_name']) && !empty($item['customer_name'])) {
                    $item['customer_name'] = $item['customer_name'];
                } elseif (isset($item['customer_id']) && $item['customer_id']) {
                    try {
                        $customer = $this->customerRepository->getById($item['customer_id']);
                        $item['customer_name'] = $customer->getFirstname() . ' ' . $customer->getLastname();
                    } catch (\Exception $e) {
                        $item['customer_name'] = __('N/A');
                    }
                } else {
                    $item['customer_name'] = __('Guest');
                }
            }
        }

        return $dataSource;
    }
}
