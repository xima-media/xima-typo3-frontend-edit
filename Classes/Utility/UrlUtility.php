<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Xima\XimaTypo3FrontendEdit\Configuration;

class UrlUtility
{
    /**
    * @throws \TYPO3\CMS\Frontend\Typolink\UnableToLinkException
    */
    public static function getUrl(int $pageId, ?int $languageUid = 0, bool $forceAbsoluteUrl = true): string
    {
        /** @var ContentObjectRenderer $contentObjectRenderer */
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $typolinkConfiguration = [
            'parameter' => $pageId,
            'language' => $languageUid,
            'forceAbsoluteUrl' => $forceAbsoluteUrl,
        ];

        return $contentObjectRenderer->typoLink_URL($typolinkConfiguration);
    }

    public static function buildUrl(string $route, array $parameters): string
    {
        $useRedirect = false;
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        if (isset($extensionConfiguration->get(Configuration::EXT_KEY)['useRedirect']) && $extensionConfiguration->get(Configuration::EXT_KEY)['useRedirect'] === true) {
            $useRedirect = true;
        }

        if ($useRedirect) {
            return GeneralUtility::makeInstance(UriBuilder::class)->buildUriWithRedirect('main', [], RouteRedirect::create($route, $parameters))->__toString();
        }

        return GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute($route, $parameters)->__toString();
    }
}
