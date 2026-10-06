<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Ui\Component\Listing\Column;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Ui\Component\Listing\Column\Actions;
use Panth\PriceDropAlert\Ui\Component\Listing\Column\CurrentPrice;
use Panth\PriceDropAlert\Ui\Component\Listing\Column\CustomerName;
use Panth\PriceDropAlert\Ui\Component\Listing\Column\Email;
use Panth\PriceDropAlert\Ui\Component\Listing\Column\ProductName;
use PHPUnit\Framework\TestCase;

class ColumnsTest extends TestCase
{
    private function source(array $items): array
    {
        return ['data' => ['items' => $items]];
    }

    private function customers(): CustomerRepositoryInterface
    {
        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getFirstname')->willReturn('Ann');
        $customer->method('getLastname')->willReturn('Lee');
        $customer->method('getEmail')->willReturn('ann@example.test');
        $repo = $this->createStub(CustomerRepositoryInterface::class);
        $repo->method('getById')->willReturnCallback(static function ($id) use ($customer) {
            if ((int) $id === 404) {
                throw new NoSuchEntityException();
            }
            return $customer;
        });
        return $repo;
    }

    public function testActionsAddViewDeleteSendLinks(): void
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(static fn($route, $params) => $route . '/' . $params['alert_id']);
        $column = new Actions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $url,
            [],
            ['name' => 'actions']
        );

        $result = $column->prepareDataSource($this->source([['alert_id' => 7], ['other' => 1]]));

        $links = $result['data']['items'][0]['actions'];
        $this->assertSame('pricedropalert/alert/view/7', $links['view']['href']);
        $this->assertSame('pricedropalert/alert/delete/7', $links['delete']['href']);
        $this->assertTrue($links['delete']['post']);
        $this->assertSame('pricedropalert/alert/send/7', $links['send']['href']);
        $this->assertArrayNotHasKey('actions', $result['data']['items'][1]);
    }

    public function testDataSourceWithoutItemsIsReturnedUntouched(): void
    {
        $column = new Actions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->createStub(UrlInterface::class)
        );

        $this->assertSame(['data' => []], $column->prepareDataSource(['data' => []]));
    }

    public function testCurrentPriceFormatsResolvedPrice(): void
    {
        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPriceById')->willReturnCallback(static function (int $id) {
            if ($id === 2) {
                throw new \RuntimeException('x');
            }
            return 12.0;
        });
        $currency = $this->createStub(PriceCurrencyInterface::class);
        $currency->method('format')->willReturnCallback(static fn($v) => '$' . number_format((float) $v, 2));
        $column = new CurrentPrice(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $resolver,
            $currency
        );

        $items = $column->prepareDataSource($this->source([['product_id' => 1], ['product_id' => 2], []]))['data']['items'];

        $this->assertSame('$12.00', $items[0]['current_price']);
        $this->assertSame('N/A', (string) $items[1]['current_price']);
        $this->assertSame('N/A', (string) $items[2]['current_price']);
    }

    public function testCustomerNamePrefersStoredNameThenAccountThenGuest(): void
    {
        $column = new CustomerName(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->customers()
        );

        $items = $column->prepareDataSource($this->source([
            ['customer_name' => 'Stored', 'customer_id' => 1],
            ['customer_name' => '', 'customer_id' => 1],
            ['customer_id' => 404],
            ['customer_id' => 0],
        ]))['data']['items'];

        $this->assertSame('Stored', $items[0]['customer_name']);
        $this->assertSame('Ann Lee', $items[1]['customer_name']);
        $this->assertSame('N/A', (string) $items[2]['customer_name']);
        $this->assertSame('Guest', (string) $items[3]['customer_name']);
    }

    public function testEmailUsesAccountEmailWithFallbacks(): void
    {
        $column = new Email(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->customers()
        );

        $items = $column->prepareDataSource($this->source([
            ['customer_id' => 1, 'email' => 'old@example.test'],
            ['customer_id' => 404, 'email' => 'kept@example.test'],
            ['customer_id' => 404],
            ['email' => 'guest@example.test'],
            [],
        ]))['data']['items'];

        $this->assertSame('ann@example.test', $items[0]['email']);
        $this->assertSame('kept@example.test', $items[1]['email']);
        $this->assertSame('N/A', (string) $items[2]['email']);
        $this->assertSame('guest@example.test', $items[3]['email']);
        $this->assertSame('Guest', (string) $items[4]['email']);
    }

    public function testProductNameResolvesOrMarksMissing(): void
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getName')->willReturn('Blue Hat');
        $repo = $this->createStub(ProductRepositoryInterface::class);
        $repo->method('getById')->willReturnCallback(static function ($id) use ($product) {
            if ((int) $id === 404) {
                throw new NoSuchEntityException();
            }
            return $product;
        });
        $column = new ProductName(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $repo
        );

        $items = $column->prepareDataSource($this->source([['product_id' => 1], ['product_id' => 404], ['x' => 1]]))['data']['items'];

        $this->assertSame('Blue Hat', $items[0]['product_name']);
        $this->assertSame('Product not found', (string) $items[1]['product_name']);
        $this->assertArrayNotHasKey('product_name', $items[2]);
    }
}
