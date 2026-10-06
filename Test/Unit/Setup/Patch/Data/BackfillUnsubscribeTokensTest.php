<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Panth\PriceDropAlert\Setup\Patch\Data\BackfillUnsubscribeTokens;
use PHPUnit\Framework\TestCase;

class BackfillUnsubscribeTokensTest extends TestCase
{
    private function setup_(AdapterInterface $connection): ModuleDataSetupInterface
    {
        $setup = $this->createStub(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturn('panth_price_alert');
        return $setup;
    }

    public function testSkipsWhenTokenColumnIsMissing(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('isTableExists')->willReturn(true);
        $connection->method('tableColumnExists')->willReturn(false);
        $connection->expects($this->never())->method('update');

        $patch = new BackfillUnsubscribeTokens($this->setup_($connection));
        $this->assertSame($patch, $patch->apply());
    }

    public function testSkipsWhenTableIsMissing(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('isTableExists')->willReturn(false);
        $connection->expects($this->never())->method('select');

        (new BackfillUnsubscribeTokens($this->setup_($connection)))->apply();
    }

    public function testGeneratesUniqueTokenForEveryAlertWithoutOne(): void
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $updates = [];
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('isTableExists')->willReturn(true);
        $connection->method('tableColumnExists')->willReturn(true);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturn(['3', '8']);
        $connection->method('update')->willReturnCallback(function ($table, $bind, $where) use (&$updates) {
            $updates[] = [$table, $bind['unsubscribe_token'], $where];
            return 1;
        });

        (new BackfillUnsubscribeTokens($this->setup_($connection)))->apply();

        $this->assertCount(2, $updates);
        $this->assertSame(['alert_id = ?' => 3], $updates[0][2]);
        $this->assertSame(['alert_id = ?' => 8], $updates[1][2]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $updates[0][1]);
        $this->assertNotSame($updates[0][1], $updates[1][1]);
        $this->assertSame([], BackfillUnsubscribeTokens::getDependencies());
    }
}
