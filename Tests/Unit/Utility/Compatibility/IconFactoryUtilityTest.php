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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Utility\Compatibility;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Utility\Compatibility\IconFactoryUtility;

/**
 * IconFactoryUtilityTest.
 *
 * The TYPO3 v14.0+ branch (3-arg IconFactory::mapRecordTypeToIconIdentifier(),
 * requiring a TcaSchema) is not unit-testable here: this project's root
 * Composer install is pinned to TYPO3 v13, whose IconFactory only declares
 * the 2-arg signature, so a mock built from it cannot be called with 3
 * arguments the way BackendUserUtilityTest's analogous v14-only branch is
 * also not unit-tested for the same reason. Verified instead against a real
 * TYPO3 v14 instance via Tests/Playwright/tests/generic-record-editing.spec.ts
 * (run with TYPO3_VERSION=14).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(IconFactoryUtility::class)]
final class IconFactoryUtilityTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function mapRecordTypeToIconIdentifierDelegatesToLegacyTwoArgMethodOnVersion13(): void
    {
        $this->registerVersion(13);

        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->method('mapRecordTypeToIconIdentifier')
            ->with('sys_category', ['uid' => 1])
            ->willReturn('mimetypes-x-sys_category');

        self::assertSame(
            'mimetypes-x-sys_category',
            IconFactoryUtility::mapRecordTypeToIconIdentifier($iconFactory, 'sys_category', ['uid' => 1]),
        );
    }

    private function registerVersion(int $majorVersion): void
    {
        $versionMock = $this->createMock(Typo3Version::class);
        $versionMock->method('getMajorVersion')->willReturn($majorVersion);
        GeneralUtility::addInstance(Typo3Version::class, $versionMock);
    }
}
