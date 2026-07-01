<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Ui;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use Xima\XimaTypo3FrontendEdit\Service\Ui\FlashMessageService;

/**
 * FlashMessageServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(FlashMessageService::class)]
final class FlashMessageServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function collectFromSessionReturnsEmptyArrayWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new FlashMessageService($this->createMock(LoggerInterface::class));

        self::assertSame([], $service->collectFromSession());
    }

    #[Test]
    public function collectFromSessionReturnsEmptyArrayWhenUserDataMissing(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = null;
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new FlashMessageService($this->createMock(LoggerInterface::class));

        self::assertSame([], $service->collectFromSession());
    }

    #[Test]
    public function collectFromSessionReturnsEmptyArrayWhenQueuesEmpty(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getSessionData')->willReturn([]);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new FlashMessageService($this->createMock(LoggerInterface::class));

        self::assertSame([], $service->collectFromSession());
    }

    #[Test]
    public function collectFromSessionParsesMessagesAndClearsQueues(): void
    {
        $notification = json_encode([
            'title' => 'Saved',
            'message' => 'Record saved',
            'severity' => ContextualFeedbackSeverity::OK->value,
        ]);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getSessionData')->willReturnMap([
            [FlashMessageQueue::NOTIFICATION_QUEUE, [$notification]],
            [FlashMessageQueue::FLASHMESSAGE_QUEUE, []],
        ]);
        $backendUser->expects(self::once())
            ->method('setAndSaveSessionData')
            ->with(FlashMessageQueue::NOTIFICATION_QUEUE, null);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new FlashMessageService($this->createMock(LoggerInterface::class));

        $result = $service->collectFromSession();

        self::assertCount(1, $result);
        self::assertSame('Saved', $result[0]['title']);
        self::assertSame('Record saved', $result[0]['message']);
        self::assertSame(ContextualFeedbackSeverity::OK->name, $result[0]['severity']);
    }

    #[Test]
    public function collectFromSessionUsesDefaultsForMissingFields(): void
    {
        $message = json_encode(['foo' => 'bar']);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getSessionData')->willReturnMap([
            [FlashMessageQueue::NOTIFICATION_QUEUE, [$message]],
            [FlashMessageQueue::FLASHMESSAGE_QUEUE, []],
        ]);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new FlashMessageService($this->createMock(LoggerInterface::class));

        $result = $service->collectFromSession();

        self::assertCount(1, $result);
        self::assertSame('', $result[0]['title']);
        self::assertSame('', $result[0]['message']);
        self::assertSame(ContextualFeedbackSeverity::OK->name, $result[0]['severity']);
    }

    #[Test]
    public function collectFromSessionFallsBackToOkForInvalidSeverity(): void
    {
        $message = json_encode(['title' => 'T', 'message' => 'M', 'severity' => 9999]);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getSessionData')->willReturnMap([
            [FlashMessageQueue::NOTIFICATION_QUEUE, [$message]],
            [FlashMessageQueue::FLASHMESSAGE_QUEUE, []],
        ]);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new FlashMessageService($this->createMock(LoggerInterface::class));

        $result = $service->collectFromSession();

        self::assertSame(ContextualFeedbackSeverity::OK->name, $result[0]['severity']);
    }

    #[Test]
    public function collectFromSessionLogsWarningForMalformedJson(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getSessionData')->willReturnMap([
            [FlashMessageQueue::NOTIFICATION_QUEUE, ['{invalid json']],
            [FlashMessageQueue::FLASHMESSAGE_QUEUE, []],
        ]);
        $GLOBALS['BE_USER'] = $backendUser;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $service = new FlashMessageService($logger);

        self::assertSame([], $service->collectFromSession());
    }

    #[Test]
    public function collectFromSessionLogsErrorWhenQueueProcessingThrows(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getSessionData')
            ->willThrowException(new RuntimeException('session broken'));
        $GLOBALS['BE_USER'] = $backendUser;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(2))->method('error');

        $service = new FlashMessageService($logger);

        self::assertSame([], $service->collectFromSession());
    }

    #[Test]
    public function collectFromSessionSkipsNonArraySessionData(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getSessionData')->willReturn(null);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new FlashMessageService($this->createMock(LoggerInterface::class));

        self::assertSame([], $service->collectFromSession());
    }
}
