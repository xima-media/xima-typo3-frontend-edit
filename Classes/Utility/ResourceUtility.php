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
        $jsFileLocation = 'EXT:' . Configuration::EXT_KEY . '/Resources/Public/JavaScript/frontend_edit.js';
        $cssFileLocation = 'EXT:' . Configuration::EXT_KEY . '/Resources/Public/Css/FrontendEdit.css';

        return [
            'css' => self::getCssTag($cssFileLocation, $attributes),
            'js' => self::getJsTag($jsFileLocation, $attributes),
        ];
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
