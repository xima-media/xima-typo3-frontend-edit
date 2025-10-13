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

namespace Xima\XimaTypo3FrontendEdit\Service\Ui;

use TYPO3\CMS\Core\Imaging\{Icon, IconFactory, IconSize};
use Xima\XimaTypo3FrontendEdit\Service\Configuration\VersionCompatibilityService;

/**
 * IconService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class IconService
{
    /**
     * @var array<string, Icon>
     */
    private array $iconCache = [];

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly VersionCompatibilityService $versionCompatibilityService,
    ) {}

    public function getIcon(string $identifier): Icon
    {
        if (!isset($this->iconCache[$identifier])) {
            $this->iconCache[$identifier] = $this->iconFactory->getIcon(
                $identifier,
                $this->getDefaultIconSize(),
            );
        }

        return $this->iconCache[$identifier];
    }

    public function clearCache(): void
    {
        $this->iconCache = [];
    }

    /**
     * Get the default icon size based on TYPO3 version.
     */
    public function getDefaultIconSize(): string|IconSize
    {
        return $this->versionCompatibilityService->getDefaultIconSize();
    }
}
