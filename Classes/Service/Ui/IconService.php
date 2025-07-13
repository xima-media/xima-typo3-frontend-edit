<?php

declare(strict_types=1);

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
    ) {
    }

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
