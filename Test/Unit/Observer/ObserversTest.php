<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Model\Layout\Merge;
use Panth\PriceDropAlert\Helper\Data;
use Panth\PriceDropAlert\Model\EmailSender;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use Panth\PriceDropAlert\Observer\AddPlacementLayoutHandle;
use Panth\PriceDropAlert\Observer\ProductSaveAfterCheckAlerts;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class ObserversTest extends TestCase
{
    private function helper(bool $enabled): Data
    {
        $helper = $this->createStub(Data::class);
        $helper->method('isEnabled')->willReturn($enabled);
        return $helper;
    }

    public function testLayoutHandleAddedOnProductPageWhenEnabled(): void
    {
        $update = $this->createMock(Merge::class);
        $update->expects($this->once())->method('addHandle')->with('pricedropalert_placement_after_price');
        $layout = $this->createStub(LayoutInterface::class);
        $layout->method('getUpdate')->willReturn($update);

        (new AddPlacementLayoutHandle($this->helper(true)))->execute(
            new Observer(['full_action_name' => 'catalog_product_view', 'layout' => $layout])
        );
    }

    public function testLayoutHandleSkippedOnOtherPagesOrWhenDisabled(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $layout->expects($this->never())->method('getUpdate');

        (new AddPlacementLayoutHandle($this->helper(true)))->execute(
            new Observer(['full_action_name' => 'cms_index_index', 'layout' => $layout])
        );
        (new AddPlacementLayoutHandle($this->helper(false)))->execute(
            new Observer(['full_action_name' => 'catalog_product_view', 'layout' => $layout])
        );
    }

    private function product(int $id, string $type, array $changed): Product
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getTypeId', 'dataHasChangedFor'])
            ->getMock();
        $product->method('getId')->willReturn($id);
        $product->method('getTypeId')->willReturn($type);
        $product->method('dataHasChangedFor')->willReturnCallback(
            static fn(string $field) => in_array($field, $changed, true)
        );
        return $product;
    }

    private function eventObserver($product): Observer
    {
        return new Observer(['event' => new Event(['product' => $product])]);
    }

    private function alert(int $id, int $storeId, float $subscribed, ?float $target): PriceAlert
    {
        $alert = $this->createStub(PriceAlert::class);
        $alert->method('getId')->willReturn($id);
        $alert->method('getStoreId')->willReturn($storeId);
        $alert->method('getSubscribedPrice')->willReturn($subscribed);
        $alert->method('getTargetPrice')->willReturn($target);
        return $alert;
    }

    private function saveObserver(
        CollectionFactory $factory,
        Data $helper,
        ?PriceResolver $resolver = null,
        ?EmailSender $sender = null,
        ?ConfigurableResource $configurable = null,
        ?LoggerInterface $logger = null
    ): ProductSaveAfterCheckAlerts {
        return new ProductSaveAfterCheckAlerts(
            $factory,
            $resolver ?? $this->createStub(PriceResolver::class),
            $sender ?? $this->createStub(EmailSender::class),
            $helper,
            $configurable ?? $this->createStub(ConfigurableResource::class),
            $logger ?? $this->createStub(LoggerInterface::class)
        );
    }

    public function testProductSaveIgnoredWhenModuleDisabledOrPriceUnchanged(): void
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->never())->method('create');

        $this->saveObserver($factory, $this->helper(false))
            ->execute($this->eventObserver($this->product(1, 'simple', ['price'])));
        $this->saveObserver($factory, $this->helper(true))
            ->execute($this->eventObserver($this->product(1, 'simple', ['name'])));
        $this->saveObserver($factory, $this->helper(true))
            ->execute($this->eventObserver(null));
    }

    public function testSimpleProductAlsoChecksConfigurableParents(): void
    {
        $filters = [];
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $cond) use (&$filters, $collection) {
                $filters[$field] = $cond;
                return $collection;
            }
        );
        $collection->method('getSize')->willReturn(0);
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $configurable = $this->createStub(ConfigurableResource::class);
        $configurable->method('getParentIdsByChild')->willReturn(['20', '21']);

        $this->saveObserver($factory, $this->helper(true), null, null, $configurable)
            ->execute($this->eventObserver($this->product(5, 'simple', ['special_price'])));

        $this->assertSame(['in' => [5, 20, 21]], $filters['product_id']);
        $this->assertSame(PriceAlert::STATUS_ACTIVE, $filters['status']);
    }

    public function testSendsImmediateAlertsWhenConditionsMet(): void
    {
        $alerts = [
            $this->alert(1, 1, 50.0, null),
            $this->alert(2, 1, 30.0, null),
            $this->alert(3, 1, 90.0, 40.0),
            $this->alert(4, 1, 90.0, 35.0),
        ];
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn(count($alerts));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($alerts));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPriceForAlert')->willReturn(40.0);
        $sent = [];
        $sender = $this->createMock(EmailSender::class);
        $sender->expects($this->exactly(2))->method('sendAlertEmail')
            ->willReturnCallback(function (PriceAlert $a) use (&$sent) {
                $sent[] = $a->getId();
                return true;
            });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with($this->stringContains('sent 2 immediate'));

        $this->saveObserver($factory, $this->helper(true), $resolver, $sender, null, $logger)
            ->execute($this->eventObserver($this->product(9, 'configurable', ['price'])));

        $this->assertSame([1, 3], $sent);
    }

    public function testSendErrorsAreLoggedPerAlert(): void
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn(1);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$this->alert(7, 1, 50.0, null)]));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPriceForAlert')->willReturn(10.0);
        $sender = $this->createStub(EmailSender::class);
        $sender->method('sendAlertEmail')->willThrowException(new \RuntimeException('fail'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('#7: fail'));
        $logger->expects($this->never())->method('info');

        $this->saveObserver($factory, $this->helper(true), $resolver, $sender, null, $logger)
            ->execute($this->eventObserver($this->product(9, 'bundle', ['special_to_date'])));
    }
}
