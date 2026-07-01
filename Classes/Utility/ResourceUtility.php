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

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Utility\{GeneralUtility, PathUtility};
use Xima\XimaTypo3FrontendEdit\Configuration;

use function sprintf;

/**
 * ResourceUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class ResourceUtility
{
    /**
     * @param array<string, string> $attributes
     *
     * @return array<string, string>
     */
    public static function getResources(array $attributes = []): array
    {
        $additionalResources = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerAdditionalFrontendResources'] ?? [];
        array_walk($additionalResources, static function (&$resource) {
            if (str_ends_with($resource, '.css')) {
                $resource = self::getCssTag($resource, []);
            } elseif (str_ends_with($resource, '.js')) {
                $resource = self::getJsTag($resource, []);
            } else {
                $resource = '';
            }
        });

        return array_merge([
            'css' => self::getCssTag('EXT:'.Configuration::EXT_KEY.'/Resources/Public/Css/FrontendEdit.css', $attributes),
            'js' => self::getJsTag('EXT:'.Configuration::EXT_KEY.'/Resources/Public/JavaScript/frontend_edit.js', $attributes),
        ], $additionalResources);
    }

    /**
     * @param array<string, string> $attributes
     */
    protected static function getCssTag(string $cssFileLocation, array $attributes): string
    {
        return sprintf(
            '<link %s />',
            GeneralUtility::implodeAttributes([
                ...$attributes,
                'rel' => 'stylesheet',
                'media' => 'all',
                'href' => self::versionedWebPath($cssFileLocation),
            ], true),
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    protected static function getJsTag(string $jsFileLocation, array $attributes): string
    {
        return sprintf(
            '<script %s></script>',
            GeneralUtility::implodeAttributes([
                ...$attributes,
                'src' => self::versionedWebPath($jsFileLocation),
            ], true),
        );
    }

    /**
     * Web path with a modification-time cache buster.
     *
     * The published _assets URL is stable and served with a far-future cache
     * header, so without a version query browsers keep a stale copy after an
     * extension update. Appending the file's mtime invalidates it on change.
     */
    protected static function versionedWebPath(string $fileLocation): string
    {
        $absolutePath = GeneralUtility::getFileAbsFileName($fileLocation);
        $webPath = PathUtility::getAbsoluteWebPath($absolutePath);
        $modificationTime = @filemtime($absolutePath);

        return false !== $modificationTime ? $webPath.'?'.$modificationTime : $webPath;
    }
}
