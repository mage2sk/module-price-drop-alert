<?php
namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Panth\PriceDropAlert\Model\EmailSender;
use Magento\Ui\Component\MassAction\Filter;

class MassSend extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alert_send';

    protected $filter;

    protected $collectionFactory;

    protected $emailSender;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        EmailSender $emailSender
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->emailSender = $emailSender;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $sentCount = 0;
            $errorCount = 0;

            foreach ($collection as $alert) {
                try {
                    $this->emailSender->sendAlertEmail($alert);
                    $sentCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                }
            }

            if ($sentCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 email(s) have been sent.', $sentCount)
                );
            }

            if ($errorCount > 0) {
                $this->messageManager->addWarningMessage(
                    __('%1 email(s) could not be sent.', $errorCount)
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
