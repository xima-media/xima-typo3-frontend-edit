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

namespace Xima\XimaTypo3FrontendEdit\Service\Ui;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * UrlBuilderService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class UrlBuilderService
{
    private UriBuilder $uriBuilder;

    public function __construct()
    {
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    /**
     * @throws RouteNotFoundException
     */
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
            ],
        )->__toString();
    }

    /**
     * @throws RouteNotFoundException
     */
    public function buildPageLayoutUrl(
        int $pageId,
        int $languageUid,
        string $returnUrl,
        ?int $contentElementUid = null,
    ): string {
        $parameters = [
            'id' => $pageId,
            'language' => $languageUid,
            'returnUrl' => $returnUrl,
        ];

        if (null !== $contentElementUid) {
            $parameters['scrollToElement'] = $contentElementUid;
        }

        return $this->uriBuilder->buildUriFromRoute('web_layout', $parameters)->__toString();
    }

    /**
     * @throws RouteNotFoundException
     */
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
            ],
        )->__toString();
    }

    /**
     * @throws RouteNotFoundException
     */
    public function buildInfoUrl(int $uid, string $table, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'show_item',
            [
                'uid' => $uid,
                'table' => $table,
                'returnUrl' => $returnUrl,
            ],
        )->__toString();
    }

    /**
     * @throws RouteNotFoundException
     */
    public function buildMoveUrl(int $uid, string $table, int $pageId, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'move_element',
            [
                'uid' => $uid,
                'table' => $table,
                'expandPage' => $pageId,
                'returnUrl' => $returnUrl,
            ],
        )->__toString();
    }

    /**
     * @throws RouteNotFoundException
     */
    public function buildHistoryUrl(int $uid, string $table, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'record_history',
            [
                'element' => $table.':'.$uid,
                'returnUrl' => $returnUrl,
            ],
        )->__toString();
    }

    /**
     * @throws RouteNotFoundException
     */
    public function buildNewContentAfterUrl(int $uid, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    'tt_content' => [-$uid => 'new'],
                ],
                'returnUrl' => $returnUrl,
            ],
        )->__toString();
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws RouteNotFoundException
     */
    public function buildRoute(string $route, array $parameters = []): string
    {
        return $this->uriBuilder->buildUriFromRoute($route, $parameters)->__toString();
    }
}
