<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\ScopeInterface;

class RateLimiter
{
    public const XML_PATH_LIMIT = 'pricedropalert/security/rate_limit';
    public const XML_PATH_WINDOW = 'pricedropalert/security/rate_limit_window';

    private const CACHE_PREFIX = 'panth_pricedropalert_rl_';
    private const DEFAULT_WINDOW = 600;

    private CacheInterface $cache;
    private ScopeConfigInterface $scopeConfig;
    private RemoteAddress $remoteAddress;

    public function __construct(
        CacheInterface $cache,
        ScopeConfigInterface $scopeConfig,
        RemoteAddress $remoteAddress
    ) {
        $this->cache = $cache;
        $this->scopeConfig = $scopeConfig;
        $this->remoteAddress = $remoteAddress;
    }

    public function isAllowed(string $action): bool
    {
        $limit = (int) $this->scopeConfig->getValue(self::XML_PATH_LIMIT, ScopeInterface::SCOPE_STORE);
        if ($limit <= 0) {
            return true;
        }

        $window = (int) $this->scopeConfig->getValue(self::XML_PATH_WINDOW, ScopeInterface::SCOPE_STORE);
        if ($window <= 0) {
            $window = self::DEFAULT_WINDOW;
        }

        $clientIp = (string) $this->remoteAddress->getRemoteAddress();
        if ($clientIp === '') {
            $clientIp = 'unknown';
        }

        $bucket = (int) floor(time() / $window);
        $cacheKey = self::CACHE_PREFIX . sha1($action . '|' . $clientIp . '|' . $bucket);
        $count = (int) $this->cache->load($cacheKey);
        if ($count >= $limit) {
            return false;
        }

        $this->cache->save((string) ($count + 1), $cacheKey, [], $window);
        return true;
    }
}
