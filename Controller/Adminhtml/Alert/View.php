<?php
namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Magento\Framework\Registry;

class View extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alert_view';

    protected $resultPageFactory;

    protected $priceAlertFactory;

    protected $coreRegistry;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        PriceAlertFactory $priceAlertFactory,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->priceAlertFactory = $priceAlertFactory;
        $this->coreRegistry = $coreRegistry;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('alert_id');
        $model = $this->priceAlertFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This alert no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('pricedropalert_alert', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_PriceDropAlert::alerts_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('View Alert #%1', $model->getId()));

        return $resultPage;
    }
}
