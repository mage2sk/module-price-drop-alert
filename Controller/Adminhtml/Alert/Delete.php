<?php
namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\PriceDropAlert\Model\PriceAlertFactory;

class Delete extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alert_delete';

    protected $priceAlertFactory;

    public function __construct(
        Context $context,
        PriceAlertFactory $priceAlertFactory
    ) {
        parent::__construct($context);
        $this->priceAlertFactory = $priceAlertFactory;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('alert_id');

        if ($id) {
            try {
                $model = $this->priceAlertFactory->create();
                $model->load($id);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This alert no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
                $model->delete();
                $this->messageManager->addSuccessMessage(__('The alert has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/view', ['alert_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find an alert to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
