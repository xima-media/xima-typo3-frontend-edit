<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2026 Konrad Michalik <hej@konradmichalik.dev>
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
     * Build URL to open TYPO3's native New Content Element Wizard.
     *
     * The wizard route (new_content_element_wizard) is an AJAX-modal fragment,
     * not a navigable page — TYPO3 core only ever opens it via
     * Modal.advanced({type: 'ajax'}) from an already-bootstrapped backend page.
     * Loading it directly yields a blank page. So instead we return a web_layout
     * (page module) URL with a hash fragment: iframe_edit.js loads the fully
     * bootstrapped page module inside the modal and auto-clicks the matching
     * wizard button, which opens the native wizard. Same mechanism on v13 and v14.
     *
     * Hash params consumed by iframe_edit.js autoClickWizardButton():
     *   colPos (required), afterUid (insert after element), container (container colPos parent).
     *
     * @param int|null $uidAfter     insert after this content element; null appends to the column
     * @param int|null $containerUid parent container UID for EXT:container columns
     *
     * @throws RouteNotFoundException
     */
    public function buildNewContentWizardUrl(
        int $pid,
        int $colPos,
        int $languageUid,
        string $returnUrl,
        ?int $uidAfter = null,
        ?int $containerUid = null,
    ): string {
        $hash = 'colPos='.$colPos;
        if (null !== $uidAfter) {
            $hash .= '&afterUid='.$uidAfter;
        }
        if (null !== $containerUid) {
            $hash .= '&container='.$containerUid;
        }

        return $this->buildPageLayoutUrlWithHash($pid, $languageUid, $returnUrl, $hash);
    }

    /**
     * Check if the contextual edit route exists (TYPO3 v14.2+).
     */
    public function isContextualEditRouteAvailable(): bool
    {
        try {
            $this->uriBuilder->buildUriFromRoute('record_edit_contextual');

            return true;
        } catch (RouteNotFoundException) {
            return false;
        }
    }

    /**
     * Build a contextual edit URL for the sidebar editor (TYPO3 v14.2+).
     *
     * Returns null if the route does not exist (TYPO3 v13 / v14.0-v14.1).
     * The returnUrl is passed through so the fullscreen edit link can return to the frontend.
     */
    public function buildContextualEditUrl(int $uid, string $table, int $languageUid, string $returnUrl = ''): ?string
    {
        try {
            $parameters = [
                'edit' => [
                    $table => [$uid => 'edit'],
                ],
                'language' => $languageUid,
            ];

            if ('' !== $returnUrl) {
                $parameters['returnUrl'] = $returnUrl;
            }

            return $this->uriBuilder->buildUriFromRoute(
                'record_edit_contextual',
                $parameters,
            )->__toString();
        } catch (RouteNotFoundException) {
            return null;
        }
    }

    /**
     * @throws RouteNotFoundException
     */
    public function buildDeleteUrl(int $uid, string $table, string $returnUrl): string
    {
        return $this->uriBuilder->buildUriFromRoute(
            'tce_db',
            [
                'cmd' => [
                    $table => [
                        $uid => ['delete' => 1],
                    ],
                ],
                'redirect' => $returnUrl,
            ],
        )->__toString();
    }

    /**
     * @throws RouteNotFoundException
     */
    public function buildToggleUrl(): string
    {
        return $this->buildRoute('ajax_frontendEdit_toggle');
    }

    /**
     * @throws RouteNotFoundException
     */
    public function buildEditInformationUrl(): string
    {
        return $this->buildRoute('ajax_frontendEdit_editInformation');
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws RouteNotFoundException
     */
    private function buildRoute(string $route, array $parameters = []): string
    {
        return $this->uriBuilder->buildUriFromRoute($route, $parameters)->__toString();
    }

    /**
     * Build a web_layout URL with a hash fragment for iframe_edit.js wizard auto-click.
     *
     * TYPO3's UriBuilder doesn't support hash fragments (they're client-side only),
     * so we append them manually. The hash is parsed by iframe_edit.js to determine
     * which "new content" wizard button to auto-click inside the iframe.
     *
     * @throws RouteNotFoundException
     */
    private function buildPageLayoutUrlWithHash(int $pid, int $languageUid, string $returnUrl, string $hash): string
    {
        $url = $this->uriBuilder->buildUriFromRoute(
            'web_layout',
            [
                'id' => $pid,
                'language' => $languageUid,
                'returnUrl' => $returnUrl,
            ],
        )->__toString();

        return $url.'#'.$hash;
    }
}
