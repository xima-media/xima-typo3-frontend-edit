<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service\Ui;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class UrlBuilderService
{
    private readonly UriBuilder $uriBuilder;

    public function __construct()
    {
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    public function buildEditUrl(int $uid, string $table, int $languageUid, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    $table => [$uid => 'edit'],
                    'language' => $languageUid,
                ],
                'returnUrl' => $returnUrl,
            ]
        )->__toString();
    }

    public function buildPageLayoutUrl(int $pageId, int $languageUid, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'web_layout',
            [
                'id' => $pageId,
                'language' => $languageUid,
                'returnUrl' => $returnUrl,
            ]
        )->__toString();
    }

    public function buildHideUrl(int $uid, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'tce_db',
            [
                'data' => [
                    'tt_content' => [
                        $uid => ['hidden' => 1],
                    ],
                ],
                'redirect' => $returnUrl,
            ]
        )->__toString();
    }

    public function buildInfoUrl(int $uid, string $table, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'show_item',
            [
                'uid' => $uid,
                'table' => $table,
                'returnUrl' => $returnUrl,
            ]
        )->__toString();
    }

    public function buildMoveUrl(int $uid, string $table, int $pageId, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'move_element',
            [
                'uid' => $uid,
                'table' => $table,
                'expandPage' => $pageId,
                'returnUrl' => $returnUrl,
            ]
        )->__toString();
    }

    public function buildHistoryUrl(int $uid, string $table, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'record_history',
            [
                'element' => $table . ':' . $uid,
                'returnUrl' => $returnUrl,
            ]
        )->__toString();
    }

    /**
    * @throws RouteNotFoundException
    */
    public function buildRoute(string $route, array $parameters = []): string
    {
        return $this->uriBuilder->buildUriFromRoute($route, $parameters)->__toString();
    }
}
