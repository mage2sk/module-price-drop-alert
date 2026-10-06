<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Cron;

use Magento\Framework\FlagManager;
use Panth\PriceDropAlert\Cron\PriceAlertNotification;
use Panth\PriceDropAlert\Helper\Data;
use Panth\PriceDropAlert\Model\EmailSender;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PriceAlertNotificationTest extends TestCase
{
    private function alert(int $id, int $storeId, float $subscribed, ?float $target): PriceAlert
    {
        $alert = $this->createStub(PriceAlert::class);
        $alert->method('getId')->willReturn($id);
        $alert->method('getStoreId')->willReturn($storeId);
        $alert->method('getProductId')->willReturn(100 + $id);
        $alert->method('getSubscribedPrice')->willReturn($subscribed);
        $alert->method('getTargetPrice')->willReturn($target);
        return $alert;
    }

    private function factory(array $alerts): CollectionFactory
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('setCurPage')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator($alerts));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        return $factory;
    }

    private function helper(array $enabledStores = [1], int $frequency = 24): Data
    {
        $helper = $this->createStub(Data::class);
        $helper->method('getCronFrequency')->willReturn($frequency);
        $helper->method('isEnabled')->willReturnCallback(
            static fn($storeId = null) => in_array((int) $storeId, $enabledStores, true)
        );
        return $helper;
    }

    private function flags(int $lastRun): FlagManager
    {
        $flags = $this->createStub(FlagManager::class);
        $flags->method('getFlagData')->willReturn($lastRun ?: null);
        return $flags;
    }

    public function testSkipsWhenLastRunIsWithinFrequency(): void
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->never())->method('create');
        $flags = $this->createMock(FlagManager::class);
        $flags->method('getFlagData')->willReturn(time() - 3600);
        $flags->expects($this->never())->method('saveFlag');

        $cron = new PriceAlertNotification(
            $factory,
            $this->createStub(PriceResolver::class),
            $this->createStub(EmailSender::class),
            $this->createStub(LoggerInterface::class),
            $this->helper([1], 24),
            $flags
        );
        $cron->execute();
    }

    public function testRunsWhenFrequencyElapsedAndRecordsRun(): void
    {
        $flags = $this->createMock(FlagManager::class);
        $flags->method('getFlagData')->willReturn(time() - 2 * 3600);
        $flags->expects($this->once())->method('saveFlag')
            ->with('pricedropalert_last_run', $this->greaterThanOrEqual(time() - 5));

        $cron = new PriceAlertNotification(
            $this->factory([]),
            $this->createStub(PriceResolver::class),
            $this->createStub(EmailSender::class),
            $this->createStub(LoggerInterface::class),
            $this->helper([1], 1),
            $flags
        );
        $cron->execute();
    }

    public function testNotifiesOnlyMatchingAlerts(): void
    {
        $dropBelowSubscribed = $this->alert(1, 1, 50.0, null);
        $noDrop = $this->alert(2, 1, 40.0, null);
        $targetReached = $this->alert(3, 1, 80.0, 45.0);
        $targetNotReached = $this->alert(4, 1, 80.0, 30.0);
        $disabledStore = $this->alert(5, 2, 99.0, null);
        $unresolved = $this->alert(6, 1, 99.0, null);

        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPriceForAlert')->willReturnCallback(
            static fn(PriceAlert $a) => [1 => 45.0, 2 => 45.0, 3 => 45.0, 4 => 45.0, 5 => 1.0, 6 => 0.0][$a->getId()]
        );
        $sent = [];
        $sender = $this->createMock(EmailSender::class);
        $sender->expects($this->exactly(2))->method('sendAlertEmail')
            ->willReturnCallback(function (PriceAlert $a) use (&$sent) {
                $sent[] = $a->getId();
                return true;
            });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->stringContains('#106'));
        $logger->expects($this->atLeastOnce())->method('info')
            ->with($this->logicalOr(
                $this->stringContains('Sent alert'),
                $this->stringContains('Checked 5 alerts, sent 2 notifications')
            ));

        $cron = new PriceAlertNotification(
            $this->factory([$dropBelowSubscribed, $noDrop, $targetReached, $targetNotReached, $disabledStore, $unresolved]),
            $resolver,
            $sender,
            $logger,
            $this->helper([1]),
            $this->flags(0)
        );
        $cron->execute();

        $this->assertSame([1, 3], $sent);
    }

    public function testSendFailureIsLoggedAndProcessingContinues(): void
    {
        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPriceForAlert')->willReturn(10.0);
        $sender = $this->createMock(EmailSender::class);
        $sender->expects($this->exactly(2))->method('sendAlertEmail')
            ->willReturnOnConsecutiveCalls($this->throwException(new \RuntimeException('smtp down')), true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('smtp down'));

        $cron = new PriceAlertNotification(
            $this->factory([$this->alert(1, 1, 20.0, null), $this->alert(2, 1, 20.0, null)]),
            $resolver,
            $sender,
            $logger,
            $this->helper([1]),
            $this->flags(0)
        );
        $cron->execute();
    }

    public function testEdgeCasesDoNotNotify(): void
    {
        $samePrice = $this->alert(1, 1, 45.0, null);
        $noSubscribedPrice = $this->alert(2, 1, 0.0, null);
        $zeroTargetUsesSubscribed = $this->alert(3, 1, 50.0, 0.0);
        $priceRose = $this->alert(4, 1, 40.0, 30.0);
        $justBelowSubscribed = $this->alert(5, 1, 45.01, null);

        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPriceForAlert')->willReturn(45.0);
        $sent = [];
        $sender = $this->createStub(EmailSender::class);
        $sender->method('sendAlertEmail')->willReturnCallback(function (PriceAlert $a) use (&$sent) {
            $sent[] = $a->getId();
            return true;
        });

        $cron = new PriceAlertNotification(
            $this->factory([$samePrice, $noSubscribedPrice, $zeroTargetUsesSubscribed, $priceRose, $justBelowSubscribed]),
            $resolver,
            $sender,
            $this->createStub(LoggerInterface::class),
            $this->helper([1]),
            $this->flags(0)
        );
        $cron->execute();

        $this->assertSame([3, 5], $sent);
    }
}
