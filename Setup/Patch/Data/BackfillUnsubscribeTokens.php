<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class BackfillUnsubscribeTokens implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('panth_price_alert');
        if (!$connection->isTableExists($table)
            || !$connection->tableColumnExists($table, 'unsubscribe_token')
        ) {
            return $this;
        }

        $select = $connection->select()
            ->from($table, ['alert_id'])
            ->where('unsubscribe_token IS NULL OR unsubscribe_token = ?', '');
        foreach ($connection->fetchCol($select) as $alertId) {
            $connection->update(
                $table,
                ['unsubscribe_token' => bin2hex(random_bytes(16))],
                ['alert_id = ?' => (int) $alertId]
            );
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
