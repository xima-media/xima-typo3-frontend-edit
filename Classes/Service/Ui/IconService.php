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

namespace Xima\XimaTypo3FrontendEdit\Service\Ui;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\VersionCompatibilityService;

final class IconService
{
    private array $iconCache = [];

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly VersionCompatibilityService $versionCompatibilityService
    ) {}

    public function getIcon(string $identifier): Icon
    {
        if (!isset($this->iconCache[$identifier])) {
            $this->iconCache[$identifier] = $this->iconFactory->getIcon(
                $identifier,
                $this->getDefaultIconSize()
            );
        }

        return $this->iconCache[$identifier];
    }

    public function clearCache(): void
    {
        $this->iconCache = [];
    }

    /**
    * Get the default icon size based on TYPO3 version
    *
    * @return string|IconSize
    */
    public function getDefaultIconSize(): string|IconSize
    {
        return $this->versionCompatibilityService->getDefaultIconSize();
    }
}
