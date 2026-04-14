<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Alert Mass Send Email Controller
 */

namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Panth\PriceDropAlert\Model\EmailSender;
use Magento\Ui\Component\MassAction\Filter;

class MassSend extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alert_send';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param EmailSender $emailSender
     */
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

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
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
