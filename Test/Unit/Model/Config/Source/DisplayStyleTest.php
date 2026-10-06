<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Panth\PriceDropAlert\Helper\Data;
use Panth\PriceDropAlert\Model\Config\Source\DisplayStyle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DisplayStyleTest extends TestCase
{
    private array $calls = [];

    private function helper($value): Data
    {
        $this->calls = [];
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path, $scope = null, $storeId = null) use ($value) {
                $this->calls[] = [$path, $scope, $storeId];
                return $path === Data::XML_PATH_DISPLAY_STYLE ? $value : null;
            }
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        return new Data($context);
    }

    public function testOptionsOfferCompactFirstAndInline(): void
    {
        $options = (new DisplayStyle())->toOptionArray();

        $this->assertSame(['compact', 'inline'], array_column($options, 'value'));
        $this->assertSame(
            ['Compact link that opens a dialog (recommended)', 'Full form shown on the page'],
            array_map(static fn ($o) => (string) $o['label'], $options)
        );
    }

    public static function styleCases(): array
    {
        return [
            'not configured' => [null, 'compact', true],
            'compact' => ['compact', 'compact', true],
            'inline' => ['inline', 'inline', false],
            'unknown value' => ['popup', 'compact', true],
        ];
    }

    #[DataProvider('styleCases')]
    public function testDisplayStyleResolution($value, string $style, bool $compact): void
    {
        $helper = $this->helper($value);

        $this->assertSame($style, $helper->getDisplayStyle(2));
        $this->assertSame($compact, $helper->isCompactStyle(2));
        $this->assertSame([Data::XML_PATH_DISPLAY_STYLE, ScopeInterface::SCOPE_STORE, 2], $this->calls[0]);
    }
}
