<?php

declare(strict_types=1);

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
