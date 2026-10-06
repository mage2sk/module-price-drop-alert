<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Url;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\PriceDropAlert\Model\EmailSender;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class EmailSenderTest extends TestCase
{
    private array $vars = [];
    private array $calls = [];

    private function alert(array $data, int $saves): PriceAlert
    {
        $alert = $this->getMockBuilder(PriceAlert::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'getId'])
            ->getMock();
        $alert->method('getId')->willReturn(5);
        $alert->expects($this->exactly($saves))->method('save')->willReturnCallback(function () use ($alert) {
            if ((string) $alert->getData('unsubscribe_token') === '') {
                $alert->setData('unsubscribe_token', 'generated');
            }
            return $alert;
        });
        $alert->setData($data);
        return $alert;
    }

    private function sender(float $alertPrice, float $productPrice = 0.0, array $config = [], ?TransportInterface $transport = null): EmailSender
    {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(2);
        $store->method('getDefaultCurrency')->willReturn($this->createStub(Currency::class));
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $product = $this->createStub(Product::class);
        $product->method('getName')->willReturn('Red Shoe');
        $product->method('getProductUrl')->willReturn('https://shop.test/red-shoe.html');
        $product->method('getTypeId')->willReturn('simple');
        $repo = $this->createStub(ProductRepositoryInterface::class);
        $repo->method('getById')->willReturn($product);

        $resolver = $this->createStub(PriceResolver::class);
        $resolver->method('getPriceForAlert')->willReturn($alertPrice);
        $resolver->method('getPrice')->willReturn($productPrice);

        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(static fn($path) => $config[$path] ?? null);

        $priceCurrency = $this->createStub(PriceCurrencyInterface::class);
        $priceCurrency->method('convertAndFormat')->willReturnCallback(
            static fn($amount) => '$' . number_format((float) $amount, 2)
        );

        $url = $this->createStub(Url::class);
        $url->method('setScope')->willReturnSelf();
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params) => $route . '?id=' . $params['id'] . '&token=' . $params['token']
        );

        $transport = $transport ?? $this->createStub(TransportInterface::class);
        $builder = $this->createStub(TransportBuilder::class);
        foreach (['setTemplateIdentifier', 'setTemplateOptions', 'setTemplateVars', 'setFromByScope', 'addTo'] as $method) {
            $builder->method($method)->willReturnCallback(function (...$args) use ($method, $builder) {
                $this->calls[$method] = $args;
                if ($method === 'setTemplateVars') {
                    $this->vars = $args[0];
                }
                return $builder;
            });
        }
        $builder->method('getTransport')->willReturn($transport);

        return new EmailSender(
            $repo,
            $builder,
            $storeManager,
            $scopeConfig,
            $resolver,
            $this->createStub(LoggerInterface::class),
            $priceCurrency,
            $url
        );
    }

    public function testSendsEmailWithComputedDiscountAndMarksSent(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())->method('sendMessage');
        $alert = $this->alert([
            'product_id' => 9,
            'store_id' => 2,
            'subscribed_price' => 100.0,
            'customer_name' => "Jane\x07Doe",
            'email' => 'jane@example.test',
            'status' => PriceAlert::STATUS_ACTIVE,
            'unsubscribe_token' => 'abc',
        ], 1);

        $this->assertTrue($this->sender(75.0, 0.0, [], $transport)->sendAlertEmail($alert));

        $this->assertSame('25', $this->vars['discount_percent']);
        $this->assertSame('1', $this->vars['has_discount']);
        $this->assertSame('100.00', $this->vars['old_price']);
        $this->assertSame('75.00', $this->vars['new_price']);
        $this->assertSame('$75.00', $this->vars['new_price_formatted']);
        $this->assertSame('Jane Doe', $this->vars['customer_name']);
        $this->assertSame('Red Shoe', $this->vars['product_name']);
        $this->assertSame('pricedropalert/unsubscribe/index?id=5&token=abc', $this->vars['unsubscribe_url']);
        $this->assertSame(['pricedropalert_email_email_template'], $this->calls['setTemplateIdentifier']);
        $this->assertSame(['general', 2], $this->calls['setFromByScope']);
        $this->assertSame(['jane@example.test', 'Jane Doe'], $this->calls['addTo']);
        $this->assertSame(PriceAlert::STATUS_SENT, $alert->getStatus());
        $this->assertNotEmpty($alert->getSentAt());
    }

    public function testMissingTokenIsGeneratedBeforeBuildingLink(): void
    {
        $alert = $this->alert([
            'subscribed_price' => 10.0,
            'status' => PriceAlert::STATUS_SENT,
            'email' => 'x@y.test',
        ], 1);

        $this->sender(8.0, 0.0, [
            'pricedropalert/email/sender' => 'sales',
            'pricedropalert/email/email_template' => 'my_tpl',
        ])->sendAlertEmail($alert);

        $this->assertStringEndsWith('token=generated', $this->vars['unsubscribe_url']);
        $this->assertSame(['my_tpl'], $this->calls['setTemplateIdentifier']);
        $this->assertSame(['sales', 2], $this->calls['setFromByScope']);
    }

    public function testFallsBackToProductPriceAndGuestNameAndNeverNegativeDiscount(): void
    {
        $alert = $this->alert([
            'subscribed_price' => 20.0,
            'customer_name' => '   ',
            'status' => PriceAlert::STATUS_SENT,
            'unsubscribe_token' => 't',
        ], 0);

        $this->sender(0.0, 30.0)->sendAlertEmail($alert);

        $this->assertSame('30.00', $this->vars['new_price']);
        $this->assertSame('0', $this->vars['discount_percent']);
        $this->assertSame('Valued Customer', (string) $this->vars['customer_name']);
        $this->assertSame('', $this->vars['has_discount']);
    }

    public function testOldPriceDefaultsToCurrentWhenNotSubscribed(): void
    {
        $alert = $this->alert(['status' => PriceAlert::STATUS_SENT, 'unsubscribe_token' => 't'], 0);

        $this->sender(12.0)->sendAlertEmail($alert);

        $this->assertSame('12.00', $this->vars['old_price']);
        $this->assertSame('0', $this->vars['discount_percent']);
        $this->assertSame('', $this->vars['has_discount']);
    }

    public function testSavingsBadgeHiddenWhenSavingRoundsBelowOnePercent(): void
    {
        $alert = $this->alert(['subscribed_price' => 100.0, 'status' => PriceAlert::STATUS_SENT, 'unsubscribe_token' => 't'], 0);

        $this->sender(99.6)->sendAlertEmail($alert);

        $this->assertSame('', $this->vars['has_discount']);
        $this->assertSame('0', $this->vars['discount_percent']);

        $alert = $this->alert(['subscribed_price' => 100.0, 'status' => PriceAlert::STATUS_SENT, 'unsubscribe_token' => 't'], 0);

        $this->sender(98.0)->sendAlertEmail($alert);

        $this->assertSame('1', $this->vars['has_discount']);
        $this->assertSame('2', $this->vars['discount_percent']);
    }
}
