<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IconUtility
{
    /**
     * ToDo: Version switch be removed in TYPO3 13
     *
     * @return string|IconSize
     */
    public static function getDefaultIconSize(): string|\TYPO3\CMS\Core\Imaging\IconSize
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();

        if ($typo3Version >= 13) {
            return \TYPO3\CMS\Core\Imaging\IconSize::SMALL;
        }
        return \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL; // @phpstan-ignore classConstant.deprecated
    }
}
