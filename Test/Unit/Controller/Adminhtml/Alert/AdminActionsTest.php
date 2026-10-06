<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Controller\Adminhtml\Alert;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use Panth\PriceDropAlert\Controller\Adminhtml\Alert\Delete;
use Panth\PriceDropAlert\Controller\Adminhtml\Alert\MassDelete;
use Panth\PriceDropAlert\Controller\Adminhtml\Alert\MassSend;
use Panth\PriceDropAlert\Controller\Adminhtml\Alert\Send;
use Panth\PriceDropAlert\Controller\Adminhtml\Alert\View;
use Panth\PriceDropAlert\Model\EmailSender;
use Panth\PriceDropAlert\Model\PriceAlert;
use Panth\PriceDropAlert\Model\PriceAlertFactory;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\Collection;
use Panth\PriceDropAlert\Model\ResourceModel\PriceAlert\CollectionFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdminActionsTest extends TestCase
{
    private array $messages = [];
    private array $redirect = [];

    private function context(array $params = []): Context
    {
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $params[$key] ?? null);

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(function ($path, $args = []) use ($redirect) {
            $this->redirect = [$path, $args];
            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $manager = $this->createStub(ManagerInterface::class);
        foreach (['addErrorMessage' => 'error', 'addSuccessMessage' => 'success', 'addWarningMessage' => 'warning'] as $method => $type) {
            $manager->method($method)->willReturnCallback(function ($m) use ($manager, $type) {
                $this->messages[] = [$type, (string) $m];
                return $manager;
            });
        }

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);
        $context->method('getMessageManager')->willReturn($manager);
        return $context;
    }

    private function model(?int $id): PriceAlert
    {
        $model = $this->getMockBuilder(PriceAlert::class)->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId', 'delete'])->getMock();
        $model->method('load')->willReturnSelf();
        $model->method('getId')->willReturn($id);
        return $model;
    }

    private function factory(PriceAlert $model): PriceAlertFactory
    {
        $factory = $this->createStub(PriceAlertFactory::class);
        $factory->method('create')->willReturn($model);
        return $factory;
    }

    private function filter(array $items, ?\Exception $error = null): Filter
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getSize')->willReturn(count($items));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
        $filter = $this->createStub(Filter::class);
        if ($error) {
            $filter->method('getCollection')->willThrowException($error);
        } else {
            $filter->method('getCollection')->willReturn($collection);
        }
        return $filter;
    }

    private function collectionFactory(): CollectionFactory
    {
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($this->createStub(Collection::class));
        return $factory;
    }

    public function testDeleteRemovesExistingAlert(): void
    {
        $model = $this->model(4);
        $model->expects($this->once())->method('delete');

        (new Delete($this->context(['alert_id' => 4]), $this->factory($model)))->execute();

        $this->assertSame([['success', 'The alert has been deleted.']], $this->messages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testDeleteHandlesMissingIdUnknownAlertAndFailure(): void
    {
        (new Delete($this->context(), $this->factory($this->model(null))))->execute();
        $this->assertSame(['error', "We can't find an alert to delete."], $this->messages[0]);

        (new Delete($this->context(['alert_id' => 9]), $this->factory($this->model(null))))->execute();
        $this->assertSame(['error', 'This alert no longer exists.'], $this->messages[1]);

        $broken = $this->model(9);
        $broken->method('delete')->willThrowException(new \RuntimeException('constraint'));
        (new Delete($this->context(['alert_id' => 9]), $this->factory($broken)))->execute();
        $this->assertSame(['error', 'constraint'], $this->messages[2]);
        $this->assertSame(['*/*/view', ['alert_id' => 9]], $this->redirect);
    }

    public function testMassDeleteDeletesEverySelectedAlert(): void
    {
        $a = $this->model(1);
        $a->expects($this->once())->method('delete');
        $b = $this->model(2);
        $b->expects($this->once())->method('delete');

        (new MassDelete($this->context(), $this->filter([$a, $b]), $this->collectionFactory()))->execute();

        $this->assertSame([['success', 'A total of 2 record(s) have been deleted.']], $this->messages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testMassDeleteReportsFilterError(): void
    {
        (new MassDelete($this->context(), $this->filter([], new \RuntimeException('bad filter')), $this->collectionFactory()))->execute();

        $this->assertSame([['error', 'bad filter']], $this->messages);
    }

    public function testSendDispatchesEmailAndReturnsToView(): void
    {
        $model = $this->model(3);
        $sender = $this->createMock(EmailSender::class);
        $sender->expects($this->once())->method('sendAlertEmail')->with($model)->willReturn(true);

        (new Send($this->context(['alert_id' => 3]), $this->factory($model), $sender))->execute();

        $this->assertSame([['success', 'The alert email has been sent.']], $this->messages);
        $this->assertSame(['*/*/view', ['alert_id' => 3]], $this->redirect);
    }

    public function testSendHandlesMissingAndFailingAlerts(): void
    {
        $sender = $this->createMock(EmailSender::class);
        $sender->expects($this->once())->method('sendAlertEmail')->willThrowException(new \RuntimeException('smtp'));

        (new Send($this->context(), $this->factory($this->model(3)), $sender))->execute();
        (new Send($this->context(['alert_id' => 3]), $this->factory($this->model(null)), $sender))->execute();
        (new Send($this->context(['alert_id' => 3]), $this->factory($this->model(3)), $sender))->execute();

        $this->assertSame([
            ['error', "We can't find an alert to send."],
            ['error', 'This alert no longer exists.'],
            ['error', 'smtp'],
        ], $this->messages);
    }

    public function testMassSendCountsSuccessesAndFailures(): void
    {
        $sender = $this->createStub(EmailSender::class);
        $sender->method('sendAlertEmail')->willReturnCallback(static function (PriceAlert $a) {
            if ($a->getId() === 2) {
                throw new \RuntimeException('fail');
            }
            return true;
        });

        (new MassSend(
            $this->context(),
            $this->filter([$this->model(1), $this->model(2), $this->model(3)]),
            $this->collectionFactory(),
            $sender
        ))->execute();

        $this->assertSame([
            ['success', 'A total of 2 email(s) have been sent.'],
            ['warning', '1 email(s) could not be sent.'],
        ], $this->messages);
    }

    public function testMassSendWithEmptySelectionAddsNoMessages(): void
    {
        (new MassSend($this->context(), $this->filter([]), $this->collectionFactory(), $this->createStub(EmailSender::class)))->execute();

        $this->assertSame([], $this->messages);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testViewRedirectsForUnknownAlert(): void
    {
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->never())->method('register');

        (new View($this->context(['alert_id' => 5]), $this->createStub(PageFactory::class), $this->factory($this->model(null)), $registry))->execute();

        $this->assertSame(['error', 'This alert no longer exists.'], $this->messages[0]);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testViewRegistersAlertAndBuildsPage(): void
    {
        $model = $this->model(5);
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->once())->method('register')->with('pricedropalert_alert', $model);
        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('prepend')
            ->with($this->callback(static fn($phrase) => (string) $phrase === 'View Alert #5'));
        $config = $this->createStub(PageConfig::class);
        $config->method('getTitle')->willReturn($title);
        $page = $this->createMock(Page::class);
        $page->expects($this->once())->method('setActiveMenu')->with('Panth_PriceDropAlert::alerts_menu');
        $page->method('getConfig')->willReturn($config);
        $pageFactory = $this->createStub(PageFactory::class);
        $pageFactory->method('create')->willReturn($page);

        $result = (new View($this->context(['alert_id' => 5]), $pageFactory, $this->factory($model), $registry))->execute();

        $this->assertSame($page, $result);
    }
}
