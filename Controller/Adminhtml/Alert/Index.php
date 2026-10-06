<?php
namespace Panth\PriceDropAlert\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Panth_PriceDropAlert::alerts';

    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_PriceDropAlert::alerts_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Price Alerts'));

        return $resultPage;
    }
}
