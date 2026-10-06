<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Controller\Alert;

use Magento\Customer\Model\Customer;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\PriceDropAlert\Controller\Alert\Price;
use Panth\PriceDropAlert\Controller\Alert\Status;
use Panth\PriceDropAlert\Controller\Alert\Unsubscribe;
use Panth\PriceDropAlert\Helper\Data;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Model\RateLimiter;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StatusAndUnsubscribeTest extends TestCase
{
    private array $result = [];
    private ?int $httpCode = null;
    private array $filters = [];

    private function context(array $params): Context
    {
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        return $context;
    }

    private function jsonFactory(): JsonFactory
    {
        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->result = $data;
            return $json;
        });
        $json->method('setHttpResponseCode')->willReturnCallback(function ($code) use ($json) {
            $this->httpCode = $code;
            return $json;
        });
        $factory = $this->createStub(JsonFactory::class);
        $factory->method('create')->willReturn($json);
        return $factory;
    }

    private function helper(bool $enabled = true): Data
    {
        $helper = $this->createStub(Data::class);
        $helper->method('isPriceAlertEnabled')->willReturn($enabled);
        return $helper;
    }

    private function factory(array $items, ?\Exception $error = null): PriceAlertFactory
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $cond) use ($collection, $error) {
                if ($error) {
                    throw $error;
                }
                $this->filters[$field] = $cond;
                return $collection;
            }
        );
        $collection->method('getSize')->willReturn(count($items));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
        $collection->method('getFirstItem')->willReturn($items[0] ?? null);
        $alert = $this->getMockBuilder(PriceAlert::class)->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])->getMock();
        $alert->method('getCollection')->willReturn($collection);
        $factory = $this->createStub(PriceAlertFactory::class);
        $factory->method('create')->willReturn($alert);
        return $factory;
    }

    private function memberSession(): CustomerSessionDouble
    {
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $customer->setData('email', 'member@example.test');
        return new CustomerSessionDouble(4, $customer);
    }

    private function storeManager(string $currency = 'USD', float $rate = 1.0): StoreManagerInterface
    {
        $base = $this->createStub(Currency::class);
        $base->method('getRate')->willReturn($rate);
        $store = $this->createStub(Store::class);
        $store->method('getCurrentCurrencyCode')->willReturn($currency);
        $store->method('getBaseCurrencyCode')->willReturn('USD');
        $store->method('getBaseCurrency')->willReturn($base);
        $manager = $this->createStub(StoreManagerInterface::class);
        $manager->method('getStore')->willReturn($store);
        return $manager;
    }

    private function storedAlert(?float $trigger): PriceAlert
    {
        $alert = $this->getMockBuilder(PriceAlert::class)->disableOriginalConstructor()
            ->onlyMethods(['delete'])->getMock();
        $alert->setData([
            'alert_id' => 31,
            'subscribed_price' => 20.0,
            'target_price' => $trigger,
            'customer_name' => 'Ann',
        ]);
        return $alert;
    }

    private function statusController(array $params, $session, PriceAlertFactory $factory, bool $enabled = true, ?StoreManagerInterface $sm = null): Status
    {
        return new Status(
            $this->context($params),
            $this->jsonFactory(),
            $session,
            $factory,
            $this->helper($enabled),
            $sm ?? $this->storeManager()
        );
    }

    public function testStatusErrorsWhenDisabledOrMissingProduct(): void
    {
        $this->statusController(['product_id' => 1], new CustomerSessionDouble(), $this->factory([]), false)->execute();
        $this->assertSame(['error' => true, 'subscribed' => false], $this->result);

        $this->statusController([], new CustomerSessionDouble(), $this->factory([]))->execute();
        $this->assertSame(['error' => true, 'subscribed' => false], $this->result);
    }

    public function testGuestWithoutSessionAlertsIsNotSubscribedEvenWithEmailParam(): void
    {
        $this->statusController(['product_id' => 1, 'email' => 'victim@example.test'], new CustomerSessionDouble(), $this->factory([$this->storedAlert(null)]))->execute();

        $this->assertSame(['subscribed' => false], $this->result);
        $this->assertSame([], $this->filters);
    }

    public function testGuestStatusIsScopedToSessionAlertIds(): void
    {
        $session = new CustomerSessionDouble(null, null, [Price::SESSION_ALERT_IDS => ['5', 0, '6']]);

        $this->statusController(['product_id' => 1], $session, $this->factory([$this->storedAlert(15.0)]))->execute();

        $this->assertTrue($this->result['subscribed']);
        $this->assertSame(['in' => [0 => 5, 2 => 6]], $this->filters['alert_id']);
        $this->assertArrayNotHasKey('email', $this->filters);
        $this->assertSame(
            ['alert_id' => 31, 'subscribed_price' => 20.0, 'trigger_price' => 15.0, 'customer_name' => 'Ann'],
            $this->result['alert']
        );
    }

    public function testMemberStatusUsesAccountEmailAndDisplayRate(): void
    {
        $this->statusController(
            ['product_id' => 1, 'email' => 'other@example.test'],
            $this->memberSession(),
            $this->factory([$this->storedAlert(null)]),
            true,
            $this->storeManager('EUR', 0.5)
        )->execute();

        $this->assertSame('member@example.test', $this->filters['email']);
        $this->assertSame(10.0, $this->result['alert']['subscribed_price']);
        $this->assertNull($this->result['alert']['trigger_price']);
    }

    public function testMemberWithoutAlertsIsNotSubscribed(): void
    {
        $this->statusController(['product_id' => 1], $this->memberSession(), $this->factory([]))->execute();

        $this->assertSame(['subscribed' => false, 'alert' => null], $this->result);
    }

    public function testStatusErrorIsSwallowed(): void
    {
        $this->statusController(['product_id' => 1], $this->memberSession(), $this->factory([], new \RuntimeException('x')))->execute();

        $this->assertSame(['error' => true, 'subscribed' => false], $this->result);
    }

    private function unsubscribe(array $params, $session, PriceAlertFactory $factory, bool $enabled = true, bool $allowed = true): Unsubscribe
    {
        $limiter = $this->createStub(RateLimiter::class);
        $limiter->method('isAllowed')->willReturn($allowed);
        return new Unsubscribe($this->context($params), $this->jsonFactory(), $session, $factory, $this->helper($enabled), $limiter);
    }

    public function testUnsubscribeGuardClauses(): void
    {
        $this->unsubscribe(['product_id' => 1], new CustomerSessionDouble(), $this->factory([]), false)->execute();
        $this->assertSame('Price alerts are disabled.', (string) $this->result['message']);

        $this->unsubscribe(['product_id' => 1], new CustomerSessionDouble(), $this->factory([]), true, false)->execute();
        $this->assertSame(429, $this->httpCode);

        $this->unsubscribe([], new CustomerSessionDouble(), $this->factory([]))->execute();
        $this->assertSame('Product ID is required.', (string) $this->result['message']);

        $this->unsubscribe(['product_id' => 1, 'email' => 'victim@example.test'], new CustomerSessionDouble(), $this->factory([]))->execute();
        $this->assertSame('No active price alert found for this product.', (string) $this->result['message']);
        $this->assertSame([], $this->filters);
    }

    public function testUnsubscribeDeletesAllMatchingAlerts(): void
    {
        $first = $this->storedAlert(null);
        $first->expects($this->once())->method('delete');
        $second = $this->storedAlert(null);
        $second->expects($this->once())->method('delete');

        $this->unsubscribe(['product_id' => 3], $this->memberSession(), $this->factory([$first, $second]))->execute();

        $this->assertTrue($this->result['success']);
        $this->assertSame('member@example.test', $this->filters['email']);
        $this->assertSame(3, $this->filters['product_id']);
    }

    public function testUnsubscribeWhenNothingMatchesOrDeleteFails(): void
    {
        $session = new CustomerSessionDouble(null, null, [Price::SESSION_ALERT_IDS => [8]]);
        $this->unsubscribe(['product_id' => 3], $session, $this->factory([]))->execute();
        $this->assertSame('No active price alert found for this product.', (string) $this->result['message']);
        $this->assertSame(['in' => [8]], $this->filters['alert_id']);

        $broken = $this->storedAlert(null);
        $broken->method('delete')->willThrowException(new \RuntimeException('locked'));
        $this->unsubscribe(['product_id' => 3], $session, $this->factory([$broken]))->execute();
        $this->assertSame('Unable to remove the price alert. Please try again later.', (string) $this->result['message']);
    }
}
