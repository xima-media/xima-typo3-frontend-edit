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

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;

/**
 * ResourceUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
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
