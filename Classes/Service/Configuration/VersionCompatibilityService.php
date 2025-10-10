<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Service\Configuration;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\{GeneralUtility, VersionNumberUtility};

/**
 * VersionCompatibilityService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
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

    public function isVersion11(): bool
    {
        return 11 === $this->getMajorVersion();
    }

    public function isVersion12(): bool
    {
        return 12 === $this->getMajorVersion();
    }

    public function isVersion13OrHigher(): bool
    {
        return $this->getMajorVersion() >= 13;
    }

    public function isVersionBelow12(): bool
    {
        return $this->getMajorVersion() < 12;
    }

    public function isVersionBelow13(): bool
    {
        return $this->getMajorVersion() < 13;
    }

    public function getContentElementConfigValueKey(): string
    {
        return $this->isVersionBelow12() ? '1' : 'value';
    }

    /**
     * Get the default icon size based on TYPO3 version.
     */
    public function getDefaultIconSize(): string|\TYPO3\CMS\Core\Imaging\IconSize
    {
        if ($this->isVersion13OrHigher()) {
            return \TYPO3\CMS\Core\Imaging\IconSize::SMALL;
        }

        return Icon::SIZE_SMALL; // @phpstan-ignore classConstant.deprecated
    }
}
