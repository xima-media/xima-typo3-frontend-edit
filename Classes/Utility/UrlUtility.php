<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
