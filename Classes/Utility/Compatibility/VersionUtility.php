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

namespace Xima\XimaTypo3FrontendEdit\Utility\Compatibility;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * VersionUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class VersionUtility
{
    public static function is14OrHigher(): bool
    {
        $typo3MajorVersion = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();

        return $typo3MajorVersion >= 14;
    }
}
