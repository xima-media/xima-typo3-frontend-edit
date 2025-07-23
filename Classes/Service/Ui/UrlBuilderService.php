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
            ]
        )->__toString();
    }

    /**
    * @throws RouteNotFoundException
    */
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
            ]
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
            ]
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
            ]
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
