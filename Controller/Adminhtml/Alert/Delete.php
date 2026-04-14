<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Alert Delete Controller
 */

namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\PriceDropAlert\Model\PriceAlertFactory;

class Delete extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alert_delete';

    /**
     * @var PriceAlertFactory
     */
    protected $priceAlertFactory;

    /**
     * @param Context $context
     * @param PriceAlertFactory $priceAlertFactory
     */
    public function __construct(
        Context $context,
        PriceAlertFactory $priceAlertFactory
    ) {
        parent::__construct($context);
        $this->priceAlertFactory = $priceAlertFactory;
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('alert_id');

        if ($id) {
            try {
                $model = $this->priceAlertFactory->create();
                $model->load($id);
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
