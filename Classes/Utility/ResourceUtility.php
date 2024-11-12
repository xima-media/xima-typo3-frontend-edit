<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;

class ResourceUtility
{
    public static function getResources(array $attributes = []): array
    {
        $additionalResources = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][Configuration::EXT_KEY]['registerAdditionalFrontendResources'] ?? [];
        array_walk($additionalResources, function (&$resource) {
            if (str_ends_with($resource, '.css')) {
                $resource = self::getCssTag($resource, []);
            } elseif (str_ends_with($resource, '.js')) {
                $resource = self::getJsTag($resource, []);
            } else {
                $resource = '';
            }
        });

        return array_merge([
            'css' => self::getCssTag('EXT:' . Configuration::EXT_KEY . '/Resources/Public/Css/FrontendEdit.css', $attributes),
            'js' => self::getJsTag('EXT:' . Configuration::EXT_KEY . '/Resources/Public/JavaScript/frontend_edit.js', $attributes),
        ], $additionalResources);
    }

    protected static function getCssTag(string $cssFileLocation, array $attributes): string
    {
        return sprintf(
            '<link %s />',
            GeneralUtility::implodeAttributes([
                ...$attributes,
                'rel' => 'stylesheet',
                'media' => 'all',
                'href' => PathUtility::getPublicResourceWebPath($cssFileLocation),
            ], true)
        );
    }

    protected static function getJsTag(string $jsFileLocation, array $attributes): string
    {
        return sprintf(
            '<script %s></script>',
            GeneralUtility::implodeAttributes([
                ...$attributes,
                'src' => PathUtility::getPublicResourceWebPath($jsFileLocation),
            ], true)
        );
    }
}
