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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsExecutedEvent;
use Xima\XimaTypo3FrontendEdit\EventListener\ContentElementMarkerEventListener;

/**
 * ContentElementMarkerEventListenerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ContentElementMarkerEventListener::class)]
final class ContentElementMarkerEventListenerTest extends TestCase
{
    private ContentElementMarkerEventListener $subject;

    protected function setUp(): void
    {
        $this->subject = new ContentElementMarkerEventListener();
    }

    #[Test]
    public function contentIsUnchangedWhenSentinelKeyIsAbsent(): void
    {
        $event = $this->createEvent('<p>Text</p>', [], ['uid' => 12]);

        ($this->subject)($event);

        self::assertSame('<p>Text</p>', $event->getContent());
    }

    #[Test]
    public function contentIsUnchangedWhenSentinelKeyIsFalsy(): void
    {
        $event = $this->createEvent('<p>Text</p>', ['xfeMarkers' => '0'], ['uid' => 12]);

        ($this->subject)($event);

        self::assertSame('<p>Text</p>', $event->getContent());
    }

    #[Test]
    public function contentIsUnchangedWhenContentIsNull(): void
    {
        $event = $this->createEvent(null, ['xfeMarkers' => '1'], ['uid' => 12]);

        ($this->subject)($event);

        self::assertNull($event->getContent());
    }

    /**
     * An empty element -- suppressed by stdWrap.required, or a plugin returning
     * nothing -- must not produce an empty marker pair.
     */
    #[Test]
    public function contentIsUnchangedWhenContentIsEmpty(): void
    {
        $event = $this->createEvent('', ['xfeMarkers' => '1'], ['uid' => 12]);

        ($this->subject)($event);

        self::assertSame('', $event->getContent());
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Test]
    #[DataProvider('unusableRecordDataProvider')]
    public function contentIsUnchangedWhenUidIsUnusable(array $data): void
    {
        $event = $this->createEvent('<p>Text</p>', ['xfeMarkers' => '1'], $data);

        ($this->subject)($event);

        self::assertSame('<p>Text</p>', $event->getContent());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function unusableRecordDataProvider(): iterable
    {
        yield 'no uid at all' => [[]];
        yield 'uid zero' => [['uid' => 0]];
        yield 'uid non numeric' => [['uid' => 'foo']];
    }

    #[Test]
    public function contentIsWrappedInMarkerPair(): void
    {
        $event = $this->createEvent('<p>Text</p>', ['xfeMarkers' => '1'], ['uid' => 12]);

        ($this->subject)($event);

        self::assertSame(
            '<!--xfe:b:tt_content:12--><p>Text</p><!--xfe:e:tt_content:12-->',
            $event->getContent(),
        );
    }

    #[Test]
    public function uidIsCastToIntegerSoThePayloadStaysNumeric(): void
    {
        $event = $this->createEvent('<p>Text</p>', ['xfeMarkers' => '1'], ['uid' => '12']);

        ($this->subject)($event);

        self::assertSame(
            '<!--xfe:b:tt_content:12--><p>Text</p><!--xfe:e:tt_content:12-->',
            $event->getContent(),
        );
    }

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $data
     */
    private function createEvent(
        ?string $content,
        array $configuration,
        array $data,
    ): AfterStdWrapFunctionsExecutedEvent {
        $contentObjectRenderer = $this->createMock(ContentObjectRenderer::class);
        $contentObjectRenderer->data = $data;

        return new AfterStdWrapFunctionsExecutedEvent($content, $configuration, $contentObjectRenderer);
    }
}
