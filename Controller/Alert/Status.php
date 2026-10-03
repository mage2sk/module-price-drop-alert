<?php
namespace Panth\PriceDropAlert\Controller\Alert;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Helper\Data as PriceAlertHelper;
use Magento\Store\Model\StoreManagerInterface;

class Status extends Action implements HttpGetActionInterface
{
    protected $resultJsonFactory;

    protected $customerSession;

    protected $priceAlertFactory;

    protected $helper;

    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        PriceAlertFactory $priceAlertFactory,
        PriceAlertHelper $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->priceAlertFactory = $priceAlertFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->helper->isPriceAlertEnabled()) {
            return $result->setData([
                'error' => true,
                'subscribed' => false
            ]);
        }

        $productId = (int) $this->getRequest()->getParam('product_id');
        $email = $this->getRequest()->getParam('email');

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'subscribed' => false
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
            if (!$guestAlertIds) {
                return $result->setData([
                    'subscribed' => false
                ]);
            }
        }

        if (!$email && !$guestAlertIds) {
            return $result->setData([
                'subscribed' => false
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

            $subscribed = $collection->getSize() > 0;
            $alertData = null;

            if ($subscribed) {
                $alert = $collection->getFirstItem();
                $rate = $this->getDisplayRate();
                $triggerPrice = $alert->getTriggerPrice();
                $alertData = [
                    'alert_id' => $alert->getAlertId(),
                    'subscribed_price' => round((float) $alert->getSubscribedPrice() * $rate, 2),
                    'trigger_price' => $triggerPrice !== null && $triggerPrice !== ''
                        ? round((float) $triggerPrice * $rate, 2)
                        : null,
                    'customer_name' => $alert->getCustomerName()
                ];
            }

            return $result->setData([
                'subscribed' => $subscribed,
                'alert' => $alertData
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'subscribed' => false
            ]);
        }
    }

    private function getDisplayRate(): float
    {
        $store = $this->storeManager->getStore();
        $displayCode = (string) $store->getCurrentCurrencyCode();
        if ($displayCode === '' || $displayCode === (string) $store->getBaseCurrencyCode()) {
            return 1.0;
        }
        $rate = (float) $store->getBaseCurrency()->getRate($displayCode);
        return $rate > 0 ? $rate : 1.0;
    }
}
