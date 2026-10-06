<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormStyleTest extends TestCase
{
    private const PREFIX = 'pa';

    public static function templateProvider(): array
    {
        return [
            'hyva' => ['price-alert.phtml'],
            'luma' => ['luma/price-alert.phtml'],
        ];
    }

    private function css(string $template): string
    {
        $path = dirname(__DIR__, 3) . '/view/frontend/templates/' . $template;
        $this->assertTrue(is_file($path));
        $source = (string) file_get_contents($path);
        $start = strpos($source, '<style>');
        $end = strpos($source, '</style>');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        return substr($source, (int) $start, (int) $end - (int) $start);
    }

    private function rule(string $css, string $selector): string
    {
        $pattern = '/(?:^|[\s}])' . preg_quote($selector, '/') . '\s*\{([^}]*)\}/';
        $this->assertSame(1, preg_match($pattern, $css, $match));
        return $match[1];
    }

    #[DataProvider('templateProvider')]
    public function testInputsAreFortySixPixelsWithNeutralBorder(string $template): void
    {
        $css = $this->css($template);
        $this->assertStringContainsString('1px solid #D4D4D4', $css);
        $this->assertMatchesRegularExpression('/height:\s*(2\.875rem|46px)/', $css);
        $this->assertStringNotContainsString('1.5px solid var(--' . self::PREFIX . '-border)', $css);
    }

    #[DataProvider('templateProvider')]
    public function testSubmitIsSolidPrimaryButton(string $template): void
    {
        $rule = $this->rule($this->css($template), '.' . self::PREFIX . '-submit');
        $this->assertStringNotContainsString('gradient', $rule);
        $this->assertDoesNotMatchRegularExpression('/font-weight:\s*700/', $rule);
        $this->assertMatchesRegularExpression('/font-weight:\s*600/', $rule);
        $this->assertMatchesRegularExpression('/font-size:\s*(15px|0\.9375rem)/', $rule);
        $this->assertMatchesRegularExpression('/border-radius:\s*(8px|0\.5rem)/', $rule);
        $this->assertMatchesRegularExpression('/min-height:\s*(44px|2\.75rem)/', $rule);
    }

    #[DataProvider('templateProvider')]
    public function testSubmitHoverDarkensWithoutLift(string $template): void
    {
        $rule = $this->rule($this->css($template), '.' . self::PREFIX . '-submit:hover');
        $this->assertStringNotContainsString('translateY', $rule);
        $this->assertStringContainsString('#115E59', $rule);
    }
}
