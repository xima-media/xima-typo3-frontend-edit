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
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Utility\Compatibility\VersionUtility;

/**
 * VersionUtilityTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(VersionUtility::class)]
final class VersionUtilityTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function is14OrHigherReturnsTrueForVersion14(): void
    {
        $this->registerVersion(14);

        self::assertTrue(VersionUtility::is14OrHigher());
    }

    #[Test]
    public function is14OrHigherReturnsTrueForVersionAbove14(): void
    {
        $this->registerVersion(15);

        self::assertTrue(VersionUtility::is14OrHigher());
    }

    #[Test]
    public function is14OrHigherReturnsFalseForVersion13(): void
    {
        $this->registerVersion(13);

        self::assertFalse(VersionUtility::is14OrHigher());
    }

    private function registerVersion(int $majorVersion): void
    {
        $versionMock = $this->createMock(Typo3Version::class);
        $versionMock->method('getMajorVersion')->willReturn($majorVersion);
        GeneralUtility::addInstance(Typo3Version::class, $versionMock);
    }
}
