<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */

namespace Panth\PriceDropAlert\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED = 'pricedropalert/general/enabled';
    const XML_PATH_ALLOW_GUESTS = 'pricedropalert/general/allow_guests';
    const XML_PATH_EMAIL_SENDER = 'pricedropalert/email/sender';
    const XML_PATH_EMAIL_TEMPLATE = 'pricedropalert/email/email_template';
    const XML_PATH_CRON_FREQUENCY = 'pricedropalert/cron/frequency';

    /**
     * Check if module is enabled
     */
    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if guest subscriptions are allowed
     */
    public function isGuestAllowed($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ALLOW_GUESTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get email sender identity
     */
    public function getEmailSender($storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'general';
    }

    /**
     * Get email template identifier
     */
    public function getEmailTemplate($storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'pricedropalert_email_email_template';
    }

    /**
     * Get cron check frequency in hours
     */
    public function getCronFrequency($storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CRON_FREQUENCY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 24;
    }

    /**
     * Alias for isEnabled
     */
    public function isPriceAlertEnabled($storeId = null): bool
    {
        return $this->isEnabled($storeId);
    }
}
