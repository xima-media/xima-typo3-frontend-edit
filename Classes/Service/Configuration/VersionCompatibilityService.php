<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Service\Configuration;

use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\{GeneralUtility, VersionNumberUtility};

/**
 * VersionCompatibilityService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class VersionCompatibilityService
{
    private ?int $majorVersion = null;

    public function getMajorVersion(): int
    {
        if (null === $this->majorVersion) {
            $this->majorVersion = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        }

        return $this->majorVersion;
    }

    public function getCurrentVersion(): string
    {
        return VersionNumberUtility::getCurrentTypo3Version();
    }

    public function getContentElementConfigValueKey(): string
    {
        return 'value';
    }

    public function getDefaultIconSize(): IconSize
    {
        return IconSize::SMALL;
    }
}
