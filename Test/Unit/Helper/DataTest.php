<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Panth\PriceDropAlert\Helper\Data;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    private function helper(array $values = [], array $flags = []): Data
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn(string $path) => $values[$path] ?? null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn(string $path) => (bool) ($flags[$path] ?? false)
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Data($context);
    }

    public function testFlagsReflectConfiguration(): void
    {
        $helper = $this->helper([], [Data::XML_PATH_ENABLED => true]);

        $this->assertTrue($helper->isEnabled());
        $this->assertTrue($helper->isPriceAlertEnabled());
        $this->assertFalse($helper->isGuestAllowed());
    }

    public function testGuestFlagIsIndependentOfEnabledFlag(): void
    {
        $helper = $this->helper([], [Data::XML_PATH_ALLOW_GUESTS => true]);

        $this->assertFalse($helper->isEnabled());
        $this->assertTrue($helper->isGuestAllowed());
    }

    public function testDefaultsApplyWhenNothingConfigured(): void
    {
        $helper = $this->helper();

        $this->assertSame('general', $helper->getEmailSender());
        $this->assertSame('pricedropalert_email_email_template', $helper->getEmailTemplate());
        $this->assertSame(24, $helper->getCronFrequency());
    }

    public function testConfiguredValuesWin(): void
    {
        $helper = $this->helper([
            Data::XML_PATH_EMAIL_SENDER => 'sales',
            Data::XML_PATH_EMAIL_TEMPLATE => 'custom_template',
            Data::XML_PATH_CRON_FREQUENCY => '6',
        ]);

        $this->assertSame('sales', $helper->getEmailSender(2));
        $this->assertSame('custom_template', $helper->getEmailTemplate(2));
        $this->assertSame(6, $helper->getCronFrequency(2));
    }

    public function testZeroFrequencyFallsBackToDaily(): void
    {
        $this->assertSame(24, $this->helper([Data::XML_PATH_CRON_FREQUENCY => '0'])->getCronFrequency());
    }
}
