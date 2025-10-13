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

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * UrlUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class UrlUtility
{
    /**
     * @throws \TYPO3\CMS\Frontend\Typolink\UnableToLinkException
     */
    public static function getUrl(
        int $pageId,
        ?int $languageUid = 0,
        bool $forceAbsoluteUrl = true,
    ): string {
        /** @var ContentObjectRenderer $contentObjectRenderer */
        $contentObjectRenderer = GeneralUtility::makeInstance(
            ContentObjectRenderer::class,
        );

        $typolinkConfiguration = [
            'parameter' => $pageId,
            'language' => $languageUid,
            'forceAbsoluteUrl' => $forceAbsoluteUrl,
        ];

        return $contentObjectRenderer->typoLink_URL($typolinkConfiguration);
    }
}
