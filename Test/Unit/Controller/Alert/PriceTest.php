<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Controller\Alert;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\PriceDropAlert\Controller\Alert\Price;
use Panth\PriceDropAlert\Helper\Data;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Model\RateLimiter;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class PriceTest extends TestCase
{
    private array $result = [];
    private ?int $httpCode = null;
    private ?PriceAlert $saved = null;

    private function customer(): Customer
    {
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $customer->setData(['email' => 'member@example.test', 'firstname' => 'Mem', 'lastname' => 'Ber']);
        return $customer;
    }

    private function controller(array $params, array $opts = []): Price
    {
        $opts += [
            'enabled' => true,
            'allowed' => true,
            'guests' => true,
            'session' => new CustomerSessionDouble(),
            'price' => 50.0,
            'existing' => 0,
            'productStatus' => 1,
            'visible' => true,
            'websites' => [1],
            'repoException' => null,
            'saveException' => null,
            'currency' => 'USD',
            'rate' => 1.0,
        ];

        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);

        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->result = $data;
            return $json;
        });
        $json->method('setHttpResponseCode')->willReturnCallback(function ($code) use ($json) {
            $this->httpCode = $code;
            return $json;
        });
        $jsonFactory = $this->createStub(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($json);

        $helper = $this->createStub(Data::class);
        $helper->method('isPriceAlertEnabled')->willReturn($opts['enabled']);
        $helper->method('isGuestAllowed')->willReturn($opts['guests']);

        $limiter = $this->createStub(RateLimiter::class);
        $limiter->method('isAllowed')->willReturn($opts['allowed']);

        $currentCurrency = $this->createStub(Currency::class);
        $currentCurrency->method('format')->willReturnCallback(static fn($v) => 'P' . number_format((float) $v, 2));
        $baseCurrency = $this->createStub(Currency::class);
        $baseCurrency->method('getRate')->willReturn($opts['rate']);
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getWebsiteId')->willReturn(1);
        $store->method('getCurrentCurrencyCode')->willReturn($opts['currency']);
        $store->method('getBaseCurrencyCode')->willReturn('USD');
        $store->method('getBaseCurrency')->willReturn($baseCurrency);
        $store->method('getCurrentCurrency')->willReturn($currentCurrency);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $product = $this->createStub(Product::class);
        $product->method('getStatus')->willReturn($opts['productStatus']);
        $product->method('isVisibleInSiteVisibility')->willReturn($opts['visible']);
        $product->method('getWebsiteIds')->willReturn($opts['websites']);
        $repo = $this->createStub(ProductRepositoryInterface::class);
        if ($opts['repoException']) {
            $repo->method('getById')->willThrowException($opts['repoException']);
        } else {
            $repo->method('getById')->willReturn($product);
        }

        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPrice')->willReturn($opts['price']);

        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn($opts['existing']);
        $lookup = $this->getMockBuilder(PriceAlert::class)->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])->getMock();
        $lookup->method('getCollection')->willReturn($collection);
        $toSave = $this->getMockBuilder(PriceAlert::class)->disableOriginalConstructor()
            ->onlyMethods(['save', 'getId'])->getMock();
        $toSave->method('getId')->willReturn(77);
        if ($opts['saveException']) {
            $toSave->method('save')->willThrowException($opts['saveException']);
        } else {
            $toSave->method('save')->willReturnCallback(function () use ($toSave) {
                $this->saved = $toSave;
                return $toSave;
            });
        }
        $factory = $this->createStub(PriceAlertFactory::class);
        $factory->method('create')->willReturnOnConsecutiveCalls($lookup, $toSave);

        return new Price(
            $context,
            $jsonFactory,
            $opts['session'],
            $storeManager,
            $factory,
            $helper,
            $repo,
            $resolver,
            $this->createStub(LoggerInterface::class),
            $limiter
        );
    }

    private function guestParams(array $extra = []): array
    {
        return $extra + ['product_id' => '9', 'email' => 'guest@example.test', 'customer_name' => 'Guest User'];
    }

    private function assertError(string $message): void
    {
        $this->assertTrue($this->result['error'] ?? false);
        $this->assertSame($message, (string) $this->result['message']);
    }

    public function testDisabledModuleIsRejected(): void
    {
        $this->controller($this->guestParams(), ['enabled' => false])->execute();
        $this->assertError('Price alerts are disabled.');
    }

    public function testRateLimitedRequestReturns429(): void
    {
        $this->controller($this->guestParams(), ['allowed' => false])->execute();
        $this->assertError('Too many requests. Please try again later.');
        $this->assertSame(429, $this->httpCode);
    }

    public function testValidationErrors(): void
    {
        $this->controller(['product_id' => '0'])->execute();
        $this->assertError('Product ID is required.');

        $this->controller($this->guestParams(), ['guests' => false])->execute();
        $this->assertError('Please sign in to subscribe to price alerts.');

        $this->controller($this->guestParams(['email' => '  ']))->execute();
        $this->assertError('Email is required for guests.');

        $this->controller($this->guestParams(['customer_name' => '<b></b>']))->execute();
        $this->assertError('Name is required for guests.');

        $this->controller($this->guestParams(['customer_name' => str_repeat('a', 256)]))->execute();
        $this->assertError('Name is too long. Maximum 255 characters allowed.');

        $this->controller($this->guestParams(['email' => 'not-an-email']))->execute();
        $this->assertError('Please enter a valid email address.');
    }

    public function testUnavailableProductsAreRejected(): void
    {
        $this->controller($this->guestParams(), ['productStatus' => 2])->execute();
        $this->assertError('This product is not available.');

        $this->controller($this->guestParams(), ['visible' => false])->execute();
        $this->assertError('This product is not available.');

        $this->controller($this->guestParams(), ['websites' => [3]])->execute();
        $this->assertError('This product is not available.');

        $this->controller($this->guestParams(), ['price' => 0.0])->execute();
        $this->assertError('This product is not available.');

        $this->controller($this->guestParams(), ['repoException' => new NoSuchEntityException()])->execute();
        $this->assertError('This product is not available.');
    }

    public function testTargetPriceValidation(): void
    {
        $this->controller($this->guestParams(['trigger_price' => '-1']))->execute();
        $this->assertError('Please enter a valid target price.');

        $this->controller($this->guestParams(['target_price' => '50']))->execute();
        $this->assertError('Target price must be lower than current price.');
    }

    public function testGuestSubscriptionIsSavedAndRememberedInSession(): void
    {
        $session = new CustomerSessionDouble(null, null, [Price::SESSION_ALERT_IDS => [3]]);

        $this->controller(
            $this->guestParams(['customer_name' => "<i>Guest</i>\tUser", 'trigger_price' => '40']),
            ['session' => $session]
        )->execute();

        $this->assertTrue($this->result['success']);
        $this->assertSame('You will be notified when the price drops to P40.00 or below.', (string) $this->result['message']);
        $this->assertNotNull($this->saved);
        $this->assertNull($this->saved->getCustomerId());
        $this->assertSame(9, $this->saved->getProductId());
        $this->assertSame('guest@example.test', $this->saved->getEmail());
        $this->assertSame('Guest User', $this->saved->getCustomerName());
        $this->assertSame(50.0, $this->saved->getSubscribedPrice());
        $this->assertSame(40.0, $this->saved->getTargetPrice());
        $this->assertSame(PriceAlert::STATUS_ACTIVE, $this->saved->getStatus());
        $this->assertSame([3, 77], $session->values[Price::SESSION_ALERT_IDS]);
    }

    public function testTargetPriceIsConvertedFromDisplayCurrency(): void
    {
        $this->controller(
            $this->guestParams(['trigger_price' => '80']),
            ['currency' => 'EUR', 'rate' => 2.0]
        )->execute();

        $this->assertTrue($this->result['success']);
        $this->assertSame(40.0, $this->saved->getTargetPrice());
        $this->assertStringContainsString('P80.00', (string) $this->result['message']);
    }

    public function testLoggedInCustomerUsesAccountDetailsWithoutTarget(): void
    {
        $session = new CustomerSessionDouble(12, $this->customer());

        $this->controller(['product_id' => '9'], ['session' => $session, 'guests' => false])->execute();

        $this->assertTrue($this->result['success']);
        $this->assertSame('You will be notified when the price drops for this product.', (string) $this->result['message']);
        $this->assertSame(12, $this->saved->getCustomerId());
        $this->assertSame('member@example.test', $this->saved->getEmail());
        $this->assertSame('Mem Ber', $this->saved->getCustomerName());
        $this->assertNull($this->saved->getTargetPrice());
        $this->assertSame([], $session->values);
    }

    public function testDuplicateSubscriptionHandling(): void
    {
        $this->controller($this->guestParams(), ['existing' => 1])->execute();
        $this->assertTrue($this->result['success']);
        $this->assertNull($this->saved);

        $session = new CustomerSessionDouble(12, $this->customer());
        $this->controller(['product_id' => '9'], ['existing' => 1, 'session' => $session])->execute();
        $this->assertError('You are already subscribed to price alerts for this product.');
    }

    public function testSaveFailureReturnsGenericError(): void
    {
        $this->controller($this->guestParams(), ['saveException' => new \RuntimeException('db')])->execute();
        $this->assertError('Unable to save the price alert. Please try again later.');
    }
}
