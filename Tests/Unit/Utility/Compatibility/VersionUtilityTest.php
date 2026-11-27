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

use KonradMichalik\Ttt\Attribute\WithInstance;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures\FakeTypo3Version;
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
    #[WithInstance(Typo3Version::class, new FakeTypo3Version(14))]
    public function is14OrHigherReturnsTrueForVersion14(): void
    {
        self::assertTrue(VersionUtility::is14OrHigher());
    }

    #[Test]
    #[WithInstance(Typo3Version::class, new FakeTypo3Version(15))]
    public function is14OrHigherReturnsTrueForVersionAbove14(): void
    {
        self::assertTrue(VersionUtility::is14OrHigher());
    }

    #[Test]
    #[WithInstance(Typo3Version::class, new FakeTypo3Version(13))]
    public function is14OrHigherReturnsFalseForVersion13(): void
    {
        self::assertFalse(VersionUtility::is14OrHigher());
    }
}
