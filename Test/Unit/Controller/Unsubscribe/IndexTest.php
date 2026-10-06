<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Controller\Unsubscribe;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\PriceDropAlert\Controller\Unsubscribe\Index;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\RateLimiter;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class IndexTest extends TestCase
{
    private array $errors = [];
    private array $successes = [];
    private ?string $redirectUrl = null;
    private array $activeFilters = [];

    private function alert(array $data): PriceAlert
    {
        $alert = $this->getMockBuilder(PriceAlert::class)->disableOriginalConstructor()
            ->onlyMethods(['save', 'getId'])->getMock();
        $alert->method('getId')->willReturn($data['alert_id'] ?? null);
        $alert->setData($data);
        return $alert;
    }

    private function controller(array $params, ?PriceAlert $lookup, array $active = [], bool $allowed = true, ?LoggerInterface $logger = null): Index
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setUrl')->willReturnCallback(function ($url) use ($redirect) {
            $this->redirectUrl = $url;
            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $messages = $this->createStub(ManagerInterface::class);
        $messages->method('addErrorMessage')->willReturnCallback(function ($m) use ($messages) {
            $this->errors[] = (string) $m;
            return $messages;
        });
        $messages->method('addSuccessMessage')->willReturnCallback(function ($m) use ($messages) {
            $this->successes[] = (string) $m;
            return $messages;
        });

        $lookupCollection = $this->createStub(Collection::class);
        $lookupCollection->method('addFieldToFilter')->willReturnSelf();
        $lookupCollection->method('setPageSize')->willReturnSelf();
        $lookupCollection->method('getFirstItem')->willReturn($lookup ?? $this->alert([]));
        $activeCollection = $this->createStub(Collection::class);
        $activeCollection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $cond) use ($activeCollection) {
                $this->activeFilters[$field] = $cond;
                return $activeCollection;
            }
        );
        $activeCollection->method('getIterator')->willReturn(new \ArrayIterator($active));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturnOnConsecutiveCalls($lookupCollection, $activeCollection);

        $limiter = $this->createStub(RateLimiter::class);
        $limiter->method('isAllowed')->willReturn($allowed);

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Index(
            $request,
            $redirectFactory,
            $messages,
            $factory,
            $limiter,
            $storeManager,
            $logger ?? $this->createStub(LoggerInterface::class)
        );
    }

    public function testRateLimitedRequestRedirectsHomeWithError(): void
    {
        $this->controller(['id' => 1, 'token' => 'a'], null, [], false)->execute();

        $this->assertSame('https://shop.test/', $this->redirectUrl);
        $this->assertSame(['Too many requests. Please try again later.'], $this->errors);
    }

    public function testInvalidLinksAreRejected(): void
    {
        $alert = $this->alert(['alert_id' => 5, 'unsubscribe_token' => 'secret', 'status' => 1]);

        $this->controller(['id' => 5, 'token' => 'wrong'], $alert)->execute();
        $this->controller(['id' => 5], $alert)->execute();
        $this->controller(['id' => 0, 'token' => 'secret'], $alert)->execute();
        $this->controller(['id' => 5, 'token' => 'secret'], $this->alert(['alert_id' => 5, 'unsubscribe_token' => '']))->execute();
        $this->controller(['id' => 5, 'token' => 'secret'], $this->alert([]))->execute();

        $this->assertCount(5, $this->errors);
        $this->assertSame('This unsubscribe link is invalid or has expired.', $this->errors[0]);
        $this->assertSame([], $this->successes);
        $this->assertSame(1, $alert->getStatus());
    }

    public function testValidLinkCancelsAllActiveAlertsForEmailAndStore(): void
    {
        $alert = $this->alert([
            'alert_id' => 5,
            'unsubscribe_token' => 'secret',
            'status' => PriceAlert::STATUS_ACTIVE,
            'email' => 'a@b.test',
            'store_id' => '2',
        ]);
        $alert->expects($this->once())->method('save');
        $other = $this->alert(['alert_id' => 6, 'status' => PriceAlert::STATUS_ACTIVE]);
        $other->expects($this->once())->method('save');

        $this->controller(['id' => 5, 'token' => 'secret'], $alert, [$other])->execute();

        $this->assertSame(PriceAlert::STATUS_CANCELLED, $alert->getStatus());
        $this->assertSame(PriceAlert::STATUS_CANCELLED, $other->getStatus());
        $this->assertSame(
            ['email' => 'a@b.test', 'store_id' => 2, 'status' => PriceAlert::STATUS_ACTIVE],
            $this->activeFilters
        );
        $this->assertSame(['You have been unsubscribed from price drop alerts.'], $this->successes);
    }

    public function testAlreadySentAlertIsNotResaved(): void
    {
        $alert = $this->alert([
            'alert_id' => 5,
            'unsubscribe_token' => 'secret',
            'status' => PriceAlert::STATUS_SENT,
        ]);
        $alert->expects($this->never())->method('save');

        $this->controller(['id' => 5, 'token' => 'secret'], $alert)->execute();

        $this->assertSame(PriceAlert::STATUS_SENT, $alert->getStatus());
        $this->assertCount(1, $this->successes);
    }

    public function testSaveFailureIsLoggedAndReported(): void
    {
        $other = $this->alert(['alert_id' => 6, 'status' => PriceAlert::STATUS_ACTIVE]);
        $other->method('save')->willThrowException(new \RuntimeException('db gone'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('db gone'));

        $this->controller(
            ['id' => 5, 'token' => 'secret'],
            $this->alert(['alert_id' => 5, 'unsubscribe_token' => 'secret', 'status' => 1]),
            [$other],
            true,
            $logger
        )->execute();

        $this->assertSame(['Unable to update your subscription. Please try again later.'], $this->errors);
    }
}
