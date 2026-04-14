<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Alert View Controller
 */

namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Magento\Framework\Registry;

class View extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alert_view';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var PriceAlertFactory
     */
    protected $priceAlertFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param PriceAlertFactory $priceAlertFactory
     * @param Registry $coreRegistry
     */
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

    /**
     * View action
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
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

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_PriceDropAlert::alerts');
        $resultPage->getConfig()->getTitle()->prepend(__('View Alert #%1', $model->getId()));

        return $resultPage;
    }
}
