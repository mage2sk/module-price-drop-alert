<?php
namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Model\EmailSender;

class Send extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alert_send';

    protected $priceAlertFactory;

    protected $emailSender;

    public function __construct(
        Context $context,
        PriceAlertFactory $priceAlertFactory,
        EmailSender $emailSender
    ) {
        parent::__construct($context);
        $this->priceAlertFactory = $priceAlertFactory;
        $this->emailSender = $emailSender;
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

                $this->emailSender->sendAlertEmail($model);
                $this->messageManager->addSuccessMessage(__('The alert email has been sent.'));

                return $resultRedirect->setPath('*/*/view', ['alert_id' => $id]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/view', ['alert_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find an alert to send.'));
        return $resultRedirect->setPath('*/*/');
    }
}
