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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * UrlUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
class UrlUtility
{
    /**
    * @throws \TYPO3\CMS\Frontend\Typolink\UnableToLinkException
    */
    public static function getUrl(
        int $pageId,
        ?int $languageUid = 0,
        bool $forceAbsoluteUrl = true
    ): string {
        /** @var ContentObjectRenderer $contentObjectRenderer */
        $contentObjectRenderer = GeneralUtility::makeInstance(
            ContentObjectRenderer::class
        );

        $typolinkConfiguration = [
            'parameter' => $pageId,
            'language' => $languageUid,
            'forceAbsoluteUrl' => $forceAbsoluteUrl,
        ];

        return $contentObjectRenderer->typoLink_URL($typolinkConfiguration);
    }
}
