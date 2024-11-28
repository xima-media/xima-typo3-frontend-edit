<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;

class UrlUtility
{
    /**
    * @throws \TYPO3\CMS\Frontend\Typolink\UnableToLinkException
    */
    public static function getUrl(int $pageId, ?int $langugageUid = null, bool $forceAbsoluteUrl = true): string
    {
        $linkFactory = GeneralUtility::makeInstance(LinkFactory::class);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $typolinkConfiguration = [
            'parameter' => $pageId,
            'language' => $langugageUid ?? 0,
        ];

        if ($forceAbsoluteUrl) {
            $typolinkConfiguration['forceAbsoluteUrl'] = true;
        }

        return $linkFactory->create('', $typolinkConfiguration, $contentObjectRenderer)->getUrl();
    }
}
