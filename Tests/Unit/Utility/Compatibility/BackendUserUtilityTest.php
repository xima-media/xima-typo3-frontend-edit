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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Utility\Compatibility\BackendUserUtility;

/**
 * BackendUserUtilityTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(BackendUserUtility::class)]
final class BackendUserUtilityTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function hasRecordEditAccessDelegatesToLegacyMethodOnVersion13(): void
    {
        $this->registerVersion(13);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('recordEditAccessInternals')
            ->with('tt_content', ['uid' => 1])
            ->willReturn(true);

        self::assertTrue(BackendUserUtility::hasRecordEditAccess($backendUser, 'tt_content', ['uid' => 1]));
    }

    #[Test]
    public function hasRecordEditAccessReturnsFalseWhenLegacyMethodDenies(): void
    {
        $this->registerVersion(13);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('recordEditAccessInternals')->willReturn(false);

        self::assertFalse(BackendUserUtility::hasRecordEditAccess($backendUser, 'tt_content', ['uid' => 2]));
    }

    private function registerVersion(int $majorVersion): void
    {
        $versionMock = $this->createMock(Typo3Version::class);
        $versionMock->method('getMajorVersion')->willReturn($majorVersion);
        GeneralUtility::addInstance(Typo3Version::class, $versionMock);
    }
}
