<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Panth\PriceDropAlert\Model\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    private function limiter(CacheInterface $cache, $limit, $window = null, $ip = '10.0.0.1'): RateLimiter
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnMap([
            [RateLimiter::XML_PATH_LIMIT, 'store', null, $limit],
            [RateLimiter::XML_PATH_WINDOW, 'store', null, $window],
        ]);
        $remote = $this->createStub(RemoteAddress::class);
        $remote->method('getRemoteAddress')->willReturn($ip);

        return new RateLimiter($cache, $scopeConfig, $remote);
    }

    public function testDisabledLimitAlwaysAllowsWithoutTouchingCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->never())->method('load');
        $cache->expects($this->never())->method('save');

        $this->assertTrue($this->limiter($cache, '0')->isAllowed('subscribe'));
    }

    public function testFirstRequestIsCountedWithConfiguredWindow(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('load')->willReturn(false);
        $cache->expects($this->once())->method('save')
            ->with('1', $this->stringStartsWith('panth_pricedropalert_rl_'), [], 120);

        $this->assertTrue($this->limiter($cache, '3', '120')->isAllowed('subscribe'));
    }

    public function testCounterIncrementsBelowLimit(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('load')->willReturn('2');
        $cache->expects($this->once())->method('save')->with('3', $this->anything(), [], 600);

        $this->assertTrue($this->limiter($cache, '3')->isAllowed('subscribe'));
    }

    public function testRequestAtLimitIsRejected(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('load')->willReturn('3');
        $cache->expects($this->never())->method('save');

        $this->assertFalse($this->limiter($cache, '3')->isAllowed('subscribe'));
    }

    public function testKeysDifferPerActionAndIp(): void
    {
        $keys = [];
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('load')->willReturnCallback(function (string $key) use (&$keys) {
            $keys[] = $key;
            return false;
        });

        $this->limiter($cache, '5', '3600')->isAllowed('subscribe');
        $this->limiter($cache, '5', '3600')->isAllowed('unsubscribe');
        $this->limiter($cache, '5', '3600', '')->isAllowed('subscribe');

        $this->assertCount(3, array_unique($keys));
    }
}
