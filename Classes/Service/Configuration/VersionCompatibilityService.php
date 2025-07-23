<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "xima_typo3_frontend_edit".
 *
 * Copyright (C) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Xima\XimaTypo3FrontendEdit\Service\Configuration;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

final class VersionCompatibilityService
{
    private ?int $majorVersion = null;

    public function getMajorVersion(): int
    {
        if ($this->majorVersion === null) {
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
        return $this->getMajorVersion() === 11;
    }

    public function isVersion12(): bool
    {
        return $this->getMajorVersion() === 12;
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
    * Get the default icon size based on TYPO3 version
    *
    * @return string|\TYPO3\CMS\Core\Imaging\IconSize
    */
    public function getDefaultIconSize(): string|\TYPO3\CMS\Core\Imaging\IconSize
    {
        if ($this->isVersion13OrHigher()) {
            return \TYPO3\CMS\Core\Imaging\IconSize::SMALL;
        }

        return \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL; // @phpstan-ignore classConstant.deprecated
    }
}
