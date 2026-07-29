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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Content;

use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Site\SiteFinder;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContentMoveService;

/**
 * ContentMoveServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ContentMoveService::class)]
final class ContentMoveServiceTest extends TestCase
{
    private ContentMoveService $subject;

    protected function setUp(): void
    {
        $this->subject = new ContentMoveService(
            new BackendUserService(),
            new SettingsService(
                $this->createMock(ExtensionConfiguration::class),
                $this->createMock(SiteFinder::class),
            ),
        );
    }

    #[Test]
    public function buildMoveCommandTargetsNegativeNeighborUidWhenInsertingAfterElement(): void
    {
        $command = $this->subject->buildMoveCommand(123, 2, 456, 10);

        self::assertSame([
            'tt_content' => [
                123 => [
                    'move' => [
                        'action' => 'paste',
                        'target' => -456,
                        'update' => ['colPos' => 2],
                    ],
                ],
            ],
        ], $command);
    }

    #[Test]
    public function buildMoveCommandTargetsPageIdWhenPlacingAtTopOfColumn(): void
    {
        $command = $this->subject->buildMoveCommand(123, 0, null, 10);

        self::assertSame(10, $command['tt_content'][123]['move']['target']);
        self::assertSame(['colPos' => 0], $command['tt_content'][123]['move']['update']);
    }

    #[Test]
    public function buildMoveCommandTreatsZeroNeighborAsTopOfColumn(): void
    {
        $command = $this->subject->buildMoveCommand(123, 5, 0, 10);

        self::assertSame(10, $command['tt_content'][123]['move']['target']);
    }

    #[Test]
    public function isMovableAcceptsPlainDefaultLanguageRecord(): void
    {
        self::assertTrue($this->subject->isMovable([
            'uid' => 1,
            'sys_language_uid' => 0,
            'tx_container_parent' => 0,
        ]));
    }

    #[Test]
    public function isMovableAcceptsAllLanguagesRecord(): void
    {
        self::assertTrue($this->subject->isMovable([
            'uid' => 1,
            'sys_language_uid' => -1,
            'tx_container_parent' => 0,
        ]));
    }

    #[Test]
    #[DataProvider('nonMovableRecordProvider')]
    public function isMovableRejectsOutOfScopeRecords(array $record): void
    {
        self::assertFalse($this->subject->isMovable($record));
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function nonMovableRecordProvider(): iterable
    {
        yield 'container child' => [['sys_language_uid' => 0, 'tx_container_parent' => 99]];
        yield 'translated element' => [['sys_language_uid' => 1, 'tx_container_parent' => 0]];
    }
}
