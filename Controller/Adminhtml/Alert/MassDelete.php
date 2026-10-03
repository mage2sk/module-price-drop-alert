<?php
namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alert_delete';

    protected $filter;

    protected $collectionFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();

            foreach ($collection as $alert) {
                $alert->delete();
            }

            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $collectionSize)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
