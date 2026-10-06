<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Block;

use Magento\Backend\Block\Template\Context as BackendContext;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ObjectManager as AppObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Panth\PriceDropAlert\Block\Adminhtml\Alert\View;
use Panth\PriceDropAlert\Block\Adminhtml\Dashboard;
use Panth\PriceDropAlert\Block\PriceAlert as PriceAlertBlock;
use Panth\PriceDropAlert\Helper\Data;
use Panth\PriceDropAlert\Model\Config\Source\Placement;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Panth\PriceDropAlert\Test\Unit\Controller\Alert\CustomerSessionDouble;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BlocksTest extends TestCase
{
    private bool $compact = true;

    protected function setUp(): void
    {
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(fn(string $type) => $this->createStub($type));
        AppObjectManager::setInstance($objectManager);
    }

    private function urlBuilder(): UrlInterface
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params = []) => $route . ($params ? '?' . http_build_query($params) : '')
        );
        return $url;
    }

    private function frontendBlock($session, ?Product $registryProduct, float $price = 0.0): PriceAlertBlock
    {
        $context = $this->createStub(Context::class);
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder());
        $registry = $this->createStub(Registry::class);
        $registry->method('registry')->willReturn($registryProduct);
        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPrice')->willReturn($price);
        $pricing = $this->createStub(PricingHelper::class);
        $pricing->method('currency')->willReturnCallback(
            static fn($v, $format = true) => $format ? 'EUR ' . number_format((float) $v * 0.9, 2) : (float) $v * 0.9
        );
        $helper = $this->createStub(Data::class);
        $helper->method('isPriceAlertEnabled')->willReturn(true);
        $helper->method('isCompactStyle')->willReturn($this->compact);

        return new PriceAlertBlock(
            $context,
            $helper,
            $session,
            $registry,
            $this->createStub(ProductRepositoryInterface::class),
            $pricing,
            new Placement(),
            $resolver
        );
    }

    private function product(int $id): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);
        return $product;
    }

    public function testFrontendBlockUsesRegistryProductAndPrice(): void
    {
        $block = $this->frontendBlock(new CustomerSessionDouble(), $this->product(8), 20.0);

        $this->assertTrue($block->isEnabled());
        $this->assertSame(8, $block->getProductId());
        $this->assertSame(20.0, $block->getProductPrice());
        $this->assertTrue($block->hasValidPrice());
        $this->assertSame(18.0, $block->getDisplayPrice());
        $this->assertSame('EUR 18.00', $block->getFormattedPrice());
        $this->assertNull($block->getCustomerEmail());
        $this->assertNull($block->getCustomerName());
        $this->assertFalse($block->isCustomerLoggedIn());
        $this->assertSame('pricedropalert/alert/price', $block->getSubscribeUrl());
        $this->assertSame('pricedropalert/alert/unsubscribe', $block->getUnsubscribeUrl());
        $this->assertSame('pricedropalert/alert/status', $block->getStatusUrl());
        $this->assertSame('price-alert-placement-after-price', $block->getPlacementClass());
    }

    public function testFrontendBlockPrefersExplicitProductData(): void
    {
        $block = $this->frontendBlock(new CustomerSessionDouble(), $this->product(8));
        $block->setData('product', $this->product(99));

        $this->assertSame(99, $block->getProductId());
    }

    public function testFrontendBlockReportsDisplayStyle(): void
    {
        $session = new CustomerSessionDouble();
        $this->assertTrue($this->frontendBlock($session, null)->isCompact());
        $this->compact = false;
        $this->assertFalse($this->frontendBlock($session, null)->isCompact());
    }

    public function testFrontendBlockWithoutProduct(): void
    {
        $block = $this->frontendBlock(new CustomerSessionDouble(), null, 50.0);

        $this->assertNull($block->getProductId());
        $this->assertSame(0, $block->getProductPrice());
        $this->assertFalse($block->hasValidPrice());
    }

    public function testFrontendBlockExposesLoggedInCustomer(): void
    {
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $customer->setData(['email' => 'm@example.test', 'firstname' => 'Max', 'lastname' => '']);

        $block = $this->frontendBlock(new CustomerSessionDouble(3, $customer), null);

        $this->assertTrue($block->isCustomerLoggedIn());
        $this->assertSame('m@example.test', $block->getCustomerEmail());
        $this->assertSame('Max', $block->getCustomerName());
    }

    private function viewBlock(PriceAlert $alert, ?ProductRepositoryInterface $repo = null, ?PriceResolver $resolver = null): View
    {
        $context = $this->createStub(BackendContext::class);
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder());
        $registry = $this->createStub(Registry::class);
        $registry->method('registry')->willReturnCallback(
            static fn(string $key) => $key === 'pricedropalert_alert' ? $alert : null
        );
        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getEmail')->willReturn('account@example.test');
        $customers = $this->createStub(CustomerRepositoryInterface::class);
        $customers->method('getById')->willReturnCallback(static function ($id) use ($customer) {
            if ((int) $id === 404) {
                throw new NoSuchEntityException();
            }
            return $customer;
        });
        $currency = $this->createStub(PriceCurrencyInterface::class);
        $currency->method('format')->willReturnCallback(static fn($v) => '$' . number_format((float) $v, 2));

        return new View(
            $context,
            $registry,
            $repo ?? $this->createStub(ProductRepositoryInterface::class),
            $customers,
            $currency,
            $resolver ?? $this->createStub(PriceResolver::class)
        );
    }

    private function alert(array $data): PriceAlert
    {
        $alert = $this->getMockBuilder(PriceAlert::class)->disableOriginalConstructor()
            ->onlyMethods(['getId'])->getMock();
        $alert->method('getId')->willReturn($data['alert_id'] ?? null);
        $alert->setData($data);
        return $alert;
    }

    public function testViewStatusLabels(): void
    {
        $labels = [];
        foreach ([PriceAlert::STATUS_ACTIVE, PriceAlert::STATUS_SENT, PriceAlert::STATUS_CANCELLED, 99] as $status) {
            $labels[] = (string) $this->viewBlock($this->alert(['status' => $status]))->getStatusLabel();
        }

        $this->assertSame(['Active/Pending', 'Sent/Notified', 'Cancelled', 'Unknown'], $labels);
    }

    public function testViewCustomerEmailResolution(): void
    {
        $this->assertSame('account@example.test', $this->viewBlock($this->alert(['customer_id' => 1, 'email' => 'x@y.test']))->getCustomerEmail());
        $this->assertSame('x@y.test', $this->viewBlock($this->alert(['customer_id' => 404, 'email' => 'x@y.test']))->getCustomerEmail());
        $this->assertSame('N/A', (string) $this->viewBlock($this->alert([]))->getCustomerEmail());
    }

    public function testViewUrlsAndFormatting(): void
    {
        $block = $this->viewBlock($this->alert(['alert_id' => 12]));

        $this->assertSame('*/*/delete?alert_id=12', $block->getDeleteUrl());
        $this->assertSame('*/*/send?alert_id=12', $block->getSendEmailUrl());
        $this->assertSame('*/*/', $block->getBackUrl());
        $this->assertSame('$3.50', $block->formatPrice(3.5));
    }

    public function testViewCurrentProductPrice(): void
    {
        $repo = $this->createStub(ProductRepositoryInterface::class);
        $repo->method('getById')->willReturn($this->createStub(Product::class));
        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPriceForAlert')->willReturnOnConsecutiveCalls(15.0, 0.0);
        $resolver->method('getPrice')->willReturn(22.0);

        $block = $this->viewBlock($this->alert(['alert_id' => 1, 'product_id' => 3]), $repo, $resolver);
        $this->assertSame(15.0, $block->getCurrentProductPrice());
        $this->assertSame(22.0, $block->getCurrentProductPrice());

        $missing = $this->createStub(ProductRepositoryInterface::class);
        $missing->method('getById')->willThrowException(new NoSuchEntityException());
        $block = $this->viewBlock($this->alert(['alert_id' => 1, 'product_id' => 3]), $missing, $resolver);
        $this->assertNull($block->getProduct());
        $this->assertSame(0, $block->getCurrentProductPrice());
    }

    private function dashboard(AdapterInterface $connection, ?ProductRepositoryInterface $repo = null): Dashboard
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getMainTable')->willReturn('panth_price_alert');
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $context = $this->createStub(BackendContext::class);
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder());

        return new Dashboard(
            $context,
            $factory,
            $this->createStub(PriceCurrencyInterface::class),
            $repo ?? $this->createStub(ProductRepositoryInterface::class)
        );
    }

    private function connection(): AdapterInterface
    {
        $select = $this->createStub(Select::class);
        foreach (['from', 'where', 'group', 'order', 'limit'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        return $connection;
    }

    public function testDashboardTrendFillsMissingDaysWithZero(): void
    {
        $connection = $this->connection();
        $connection->method('fetchAll')->willReturn([
            ['date' => '2026-10-01', 'count' => '4'],
            ['date' => '2026-10-03', 'count' => '2'],
        ]);
        $connection->method('fetchOne')->willReturn('2026-10-03');

        $trend = $this->dashboard($connection)->getAlertTrendData();

        $this->assertCount(7, $trend);
        $this->assertSame(['date' => '2026-09-27', 'count' => 0], $trend[0]);
        $this->assertSame(['date' => '2026-10-01', 'count' => 4], $trend[4]);
        $this->assertSame(['date' => '2026-10-02', 'count' => 0], $trend[5]);
        $this->assertSame(['date' => '2026-10-03', 'count' => 2], $trend[6]);
    }

    public function testDashboardAverageTargetPrice(): void
    {
        $connection = $this->connection();
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls('12.5', null);
        $dashboard = $this->dashboard($connection);

        $this->assertSame(12.5, $dashboard->getAverageTargetPrice());
        $this->assertSame(0, $dashboard->getAverageTargetPrice());
    }

    public function testDashboardProductNameIsCachedWithFallback(): void
    {
        $product = $this->createStub(Product::class);
        $product->method('getName')->willReturn('Lamp');
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('getById')->willReturnCallback(static function ($id) use ($product) {
            if ($id === 5) {
                throw new NoSuchEntityException();
            }
            return $product;
        });
        $dashboard = $this->dashboard($this->connection(), $repo);

        $this->assertSame('Lamp', $dashboard->getProductName(1));
        $this->assertSame('Lamp', $dashboard->getProductName(1));
        $this->assertSame('Product #5', $dashboard->getProductName(5));
        $this->assertSame('pricedropalert/alert/view?alert_id=3', $dashboard->getViewAlertUrl(3));
    }
}
