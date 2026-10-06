<?php
namespace Panth\PriceDropAlert\Controller\Alert;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Panth\PriceDropAlert\Model\RateLimiter;

class Unsubscribe extends Action implements HttpPostActionInterface
{
    protected $resultJsonFactory;

    protected $customerSession;

    protected $priceAlertFactory;

    protected $helper;

    private RateLimiter $rateLimiter;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        PriceAlertFactory $priceAlertFactory,
        PriceAlertHelper $helper,
        RateLimiter $rateLimiter
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->priceAlertFactory = $priceAlertFactory;
        $this->helper = $helper;
        $this->rateLimiter = $rateLimiter;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->helper->isPriceAlertEnabled()) {
            return $result->setData([
                'error' => true,
                'message' => __('Price alerts are disabled.')
            ]);
        }

        if (!$this->rateLimiter->isAllowed('unsubscribe')) {
            $result->setHttpResponseCode(429);
            return $result->setData([
                'error' => true,
                'message' => __('Too many requests. Please try again later.')
            ]);
        }

        $productId = (int) $this->getRequest()->getParam('product_id');
        $email = $this->getRequest()->getParam('email');

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'message' => __('Product ID is required.')
            ]);
        }

        $guestAlertIds = [];
        if ($this->customerSession->isLoggedIn()) {
            $email = $this->customerSession->getCustomer()->getEmail();
        } else {
            $guestAlertIds = array_filter(array_map(
                'intval',
                (array) $this->customerSession->getData(Price::SESSION_ALERT_IDS)
            ));
        }

        if (!$email && !$guestAlertIds) {
            return $result->setData([
                'error' => true,
                'message' => __('No active price alert found for this product.')
            ]);
        }

        if (!$this->customerSession->isLoggedIn() && !$guestAlertIds) {
            return $result->setData([
                'error' => true,
                'message' => __('No active price alert found for this product.')
            ]);
        }

        try {
            $priceAlert = $this->priceAlertFactory->create();
            $collection = $priceAlert->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('status', \Panth\PriceDropAlert\Model\PriceAlert::STATUS_ACTIVE);
            if ($guestAlertIds) {
                $collection->addFieldToFilter('alert_id', ['in' => $guestAlertIds]);
            } else {
                $collection->addFieldToFilter('email', $email);
            }

            if ($collection->getSize() === 0) {
                return $result->setData([
                    'error' => true,
                    'message' => __('No active price alert found for this product.')
                ]);
            }

            foreach ($collection as $alert) {
                $alert->delete();
            }

            return $result->setData([
                'success' => true,
                'message' => __('You have been unsubscribed from price alerts for this product.')
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => __('Unable to remove the price alert. Please try again later.')
            ]);
        }
    }
}
