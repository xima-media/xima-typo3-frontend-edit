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

namespace Xima\XimaTypo3FrontendEdit\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * SmokeTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class SmokeTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    #[Test]
    public function extensionIsLoaded(): void
    {
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);

        self::assertTrue($packageManager->isPackageActive('xima_typo3_frontend_edit'));
    }
}
