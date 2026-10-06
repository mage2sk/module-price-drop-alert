<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Model;

use Panth\PriceDropAlert\Model\PriceAlert;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PriceAlertTest extends TestCase
{
    private function alert(): PriceAlert
    {
        return $this->getMockBuilder(PriceAlert::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testTriggerPriceIsAnAliasOfTargetPrice(): void
    {
        $alert = $this->alert();
        $alert->setTriggerPrice(12.5);

        $this->assertSame(12.5, $alert->getTargetPrice());
        $this->assertSame(12.5, $alert->getData('target_price'));

        $alert->setTargetPrice(9.0);
        $this->assertSame(9.0, $alert->getTriggerPrice());
    }

    public function testIdentitiesUseCacheTagAndId(): void
    {
        $alert = $this->alert();
        $alert->setId(42);

        $this->assertSame([PriceAlert::CACHE_TAG . '_42'], $alert->getIdentities());
    }

    public function testSettersAreFluentAndMapToColumns(): void
    {
        $alert = $this->alert();

        $result = $alert->setCustomerId(3)
            ->setProductId(8)
            ->setEmail('a@b.test')
            ->setCustomerName('Ann')
            ->setSubscribedPrice(20.0)
            ->setStoreId(1)
            ->setStatus(PriceAlert::STATUS_SENT)
            ->setSentAt('2026-01-01 00:00:00');

        $this->assertSame($alert, $result);
        $this->assertSame(
            [
                'customer_id' => 3,
                'product_id' => 8,
                'email' => 'a@b.test',
                'customer_name' => 'Ann',
                'subscribed_price' => 20.0,
                'store_id' => 1,
                'status' => PriceAlert::STATUS_SENT,
                'sent_at' => '2026-01-01 00:00:00',
            ],
            $alert->getData()
        );
    }

    public function testStatusConstantsAreDistinct(): void
    {
        $this->assertCount(3, array_unique([
            PriceAlert::STATUS_ACTIVE,
            PriceAlert::STATUS_SENT,
            PriceAlert::STATUS_CANCELLED,
        ]));
    }
}
