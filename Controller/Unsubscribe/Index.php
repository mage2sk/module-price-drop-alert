<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Controller\Unsubscribe;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\RateLimiter;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Psr\Log\LoggerInterface;

class Index implements HttpGetActionInterface
{
    private RequestInterface $request;
    private RedirectFactory $redirectFactory;
    private ManagerInterface $messageManager;
    private CollectionFactory $collectionFactory;
    private RateLimiter $rateLimiter;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        CollectionFactory $collectionFactory,
        RateLimiter $rateLimiter,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->collectionFactory = $collectionFactory;
        $this->rateLimiter = $rateLimiter;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        $redirect->setUrl($this->storeManager->getStore()->getBaseUrl());

        if (!$this->rateLimiter->isAllowed('unsubscribe_link')) {
            $this->messageManager->addErrorMessage(__('Too many requests. Please try again later.'));
            return $redirect;
        }

        $alertId = (int) $this->request->getParam('id');
        $token = (string) $this->request->getParam('token');

        $alert = null;
        if ($alertId > 0 && $token !== '') {
            $alert = $this->collectionFactory->create()
                ->addFieldToFilter('alert_id', $alertId)
                ->setPageSize(1)
                ->getFirstItem();
        }

        $storedToken = $alert ? (string) $alert->getData('unsubscribe_token') : '';
        if (!$alert || !$alert->getId() || $storedToken === '' || !hash_equals($storedToken, $token)) {
            $this->messageManager->addErrorMessage(__('This unsubscribe link is invalid or has expired.'));
            return $redirect;
        }

        try {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('email', (string) $alert->getEmail())
                ->addFieldToFilter('store_id', (int) $alert->getStoreId())
                ->addFieldToFilter('status', PriceAlert::STATUS_ACTIVE);
            foreach ($collection as $activeAlert) {
                $activeAlert->setStatus(PriceAlert::STATUS_CANCELLED);
                $activeAlert->save();
            }
            if ((int) $alert->getStatus() === PriceAlert::STATUS_ACTIVE) {
                $alert->setStatus(PriceAlert::STATUS_CANCELLED);
                $alert->save();
            }
            $this->messageManager->addSuccessMessage(
                __('You have been unsubscribed from price drop alerts.')
            );
        } catch (\Exception $e) {
            $this->logger->error('PriceDropAlert: token unsubscribe failed for alert #' . $alertId . ': ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Unable to update your subscription. Please try again later.'));
        }

        return $redirect;
    }
}
