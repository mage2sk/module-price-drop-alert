<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\PriceInfo\Base as PriceInfo;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class PriceResolverTest extends TestCase
{
    private function product(string $type, float $final, float $price = 0.0, int $status = 1): Product
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getFinalPrice', 'getPrice', 'getStatus', 'getPriceInfo', 'getTypeInstance', 'getId'])
            ->getMock();
        $product->method('getTypeId')->willReturn($type);
        $product->method('getFinalPrice')->willReturn($final);
        $product->method('getPrice')->willReturn($price);
        $product->method('getStatus')->willReturn($status);
        $product->method('getId')->willReturn(7);
        return $product;
    }

    private function priceInfo(?float $final, ?float $regular = null): PriceInfo
    {
        $info = $this->createStub(PriceInfo::class);
        $info->method('getPrice')->willReturnCallback(function (string $code) use ($final, $regular) {
            $value = $code === 'final_price' ? $final : $regular;
            if ($value === null) {
                return null;
            }
            $amount = $this->createStub(AmountInterface::class);
            $amount->method('getValue')->willReturn($value);
            $price = $this->createStub(FinalPrice::class);
            $price->method('getMinimalPrice')->willReturn($amount);
            return $price;
        });
        return $info;
    }

    private function resolver(
        ?ProductRepositoryInterface $repo = null,
        ?LoggerInterface $logger = null,
        ?CustomerRepositoryInterface $customers = null
    ): PriceResolver {
        return new PriceResolver(
            $repo ?? $this->createStub(ProductRepositoryInterface::class),
            $logger ?? $this->createStub(LoggerInterface::class),
            $customers ?? $this->createStub(CustomerRepositoryInterface::class)
        );
    }

    public function testSimpleUsesFinalPrice(): void
    {
        $this->assertSame(19.5, $this->resolver()->getPrice($this->product('simple', 19.5, 25.0)));
    }

    public function testSimpleFallsBackToRegularPriceWhenFinalIsZero(): void
    {
        $this->assertSame(25.0, $this->resolver()->getPrice($this->product('virtual', 0.0, 25.0)));
    }

    public function testConfigurableUsesMinimalPriceInfo(): void
    {
        $product = $this->product('configurable', 0.0);
        $product->method('getPriceInfo')->willReturn($this->priceInfo(42.0));

        $this->assertSame(42.0, $this->resolver()->getPrice($product));
    }

    public function testConfigurableFallsBackToCheapestEnabledChild(): void
    {
        $children = [
            $this->product('simple', 30.0),
            $this->product('simple', 10.0, 0.0, 2),
            $this->product('simple', 0.0, 20.0),
            $this->product('simple', 0.0, 0.0),
        ];
        $type = $this->createStub(Configurable::class);
        $type->method('getUsedProducts')->willReturn($children);
        $product = $this->product('configurable', 0.0);
        $product->method('getPriceInfo')->willReturn($this->priceInfo(0.0));
        $product->method('getTypeInstance')->willReturn($type);

        $this->assertSame(20.0, $this->resolver()->getPrice($product));
    }

    public function testConfigurableWithoutPricedChildrenReturnsZero(): void
    {
        $type = $this->createStub(Configurable::class);
        $type->method('getUsedProducts')->willReturn([]);
        $product = $this->product('configurable', 0.0);
        $product->method('getPriceInfo')->willReturn($this->priceInfo(null));
        $product->method('getTypeInstance')->willReturn($type);

        $this->assertSame(0.0, $this->resolver()->getPrice($product));
    }

    public function testConfigurableChildErrorIsLoggedAndReturnsZero(): void
    {
        $type = $this->createStub(Configurable::class);
        $type->method('getUsedProducts')->willThrowException(new \RuntimeException('boom'));
        $product = $this->product('configurable', 0.0);
        $product->method('getPriceInfo')->willReturn($this->priceInfo(0.0));
        $product->method('getTypeInstance')->willReturn($type);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('boom'));

        $this->assertSame(0.0, $this->resolver(null, $logger)->getPrice($product));
    }

    public function testBundlePrefersFinalThenRegularThenOwnPrice(): void
    {
        $resolver = $this->resolver();

        $a = $this->product('bundle', 0.0, 99.0);
        $a->method('getPriceInfo')->willReturn($this->priceInfo(15.0, 30.0));
        $this->assertSame(15.0, $resolver->getPrice($a));

        $b = $this->product('bundle', 0.0, 99.0);
        $b->method('getPriceInfo')->willReturn($this->priceInfo(0.0, 30.0));
        $this->assertSame(30.0, $resolver->getPrice($b));

        $c = $this->product('bundle', 0.0, 99.0);
        $c->method('getPriceInfo')->willReturn($this->priceInfo(0.0, 0.0));
        $this->assertSame(99.0, $resolver->getPrice($c));
    }

    public function testGroupedUsesCheapestAssociatedProduct(): void
    {
        $type = $this->createStub(Grouped::class);
        $type->method('getAssociatedProducts')->willReturn([
            $this->product('simple', 12.0),
            $this->product('simple', 0.0, 8.0),
        ]);
        $product = $this->product('grouped', 0.0);
        $product->method('getTypeInstance')->willReturn($type);

        $this->assertSame(8.0, $this->resolver()->getPrice($product));
    }

    public function testGroupedErrorReturnsZero(): void
    {
        $type = $this->createStub(Grouped::class);
        $type->method('getAssociatedProducts')->willThrowException(new \RuntimeException('x'));
        $product = $this->product('grouped', 0.0);
        $product->method('getTypeInstance')->willReturn($type);

        $this->assertSame(0.0, $this->resolver()->getPrice($product));
    }

    public function testGetPriceByIdLoadsProductForStore(): void
    {
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->expects($this->once())->method('getById')->with(5, false, 2)
            ->willReturn($this->product('simple', 11.0));

        $this->assertSame(11.0, $this->resolver($repo)->getPriceById(5, 2));
    }

    public function testGetPriceByIdWithGroupForcesReloadAndSetsGroup(): void
    {
        $product = $this->product('simple', 9.0);
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->expects($this->once())->method('getById')->with(5, false, 1, true)->willReturn($product);

        $this->assertSame(9.0, $this->resolver($repo)->getPriceById(5, 1, 3));
        $this->assertSame(3, $product->getData('customer_group_id'));
    }

    public function testGroupPriceForConfigurableResetsCachedChildPrices(): void
    {
        $enabled = $this->product('simple', 14.0);
        $enabled->setData('final_price', 1.0);
        $disabled = $this->product('simple', 2.0, 0.0, 2);
        $type = $this->createStub(Configurable::class);
        $type->method('getUsedProducts')->willReturn([$enabled, $disabled]);
        $parent = $this->product('configurable', 0.0);
        $parent->method('getTypeInstance')->willReturn($type);
        $repo = $this->createStub(ProductRepositoryInterface::class);
        $repo->method('getById')->willReturn($parent);

        $this->assertSame(14.0, $this->resolver($repo)->getPriceById(1, 1, 4));
        $this->assertNull($enabled->getData('final_price'));
        $this->assertSame(4, $enabled->getData('customer_group_id'));
        $this->assertNull($disabled->getData('customer_group_id'));
    }

    public function testGroupPriceForGroupedIncludesDisabledChildren(): void
    {
        $type = $this->createStub(Grouped::class);
        $type->method('getAssociatedProducts')->willReturn([
            $this->product('simple', 6.0, 0.0, 2),
            $this->product('simple', 9.0),
        ]);
        $parent = $this->product('grouped', 0.0);
        $parent->method('getTypeInstance')->willReturn($type);
        $repo = $this->createStub(ProductRepositoryInterface::class);
        $repo->method('getById')->willReturn($parent);

        $this->assertSame(6.0, $this->resolver($repo)->getPriceById(1, 1, 2));
    }

    public function testMissingProductIsLoggedAndReturnsZero(): void
    {
        $repo = $this->createStub(ProductRepositoryInterface::class);
        $repo->method('getById')->willThrowException(new NoSuchEntityException());
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('#77'));

        $this->assertSame(0.0, $this->resolver($repo, $logger)->getPriceById(77));
    }

    public function testCustomerGroupIsNullForGuests(): void
    {
        $customers = $this->createMock(CustomerRepositoryInterface::class);
        $customers->expects($this->never())->method('getById');

        $this->assertNull($this->resolver(null, null, $customers)->getCustomerGroupId(0));
    }

    public function testCustomerGroupIsCachedPerCustomer(): void
    {
        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getGroupId')->willReturn('3');
        $customers = $this->createMock(CustomerRepositoryInterface::class);
        $customers->expects($this->once())->method('getById')->with(10)->willReturn($customer);
        $resolver = $this->resolver(null, null, $customers);

        $this->assertSame(3, $resolver->getCustomerGroupId(10));
        $this->assertSame(3, $resolver->getCustomerGroupId(10));
    }

    public function testUnknownCustomerYieldsNullAndIsCached(): void
    {
        $customers = $this->createMock(CustomerRepositoryInterface::class);
        $customers->expects($this->once())->method('getById')->willThrowException(new NoSuchEntityException());
        $resolver = $this->resolver(null, null, $customers);

        $this->assertNull($resolver->getCustomerGroupId(11));
        $this->assertNull($resolver->getCustomerGroupId(11));
    }

    public function testGetPriceForAlertUsesAlertData(): void
    {
        $alert = $this->createStub(PriceAlert::class);
        $alert->method('getProductId')->willReturn('5');
        $alert->method('getStoreId')->willReturn('2');
        $alert->method('getCustomerId')->willReturn(null);
        $repo = $this->createMock(ProductRepositoryInterface::class);
        $repo->expects($this->once())->method('getById')->with(5, false, 2)
            ->willReturn($this->product('simple', 4.5));

        $this->assertSame(4.5, $this->resolver($repo)->getPriceForAlert($alert));
    }
}
