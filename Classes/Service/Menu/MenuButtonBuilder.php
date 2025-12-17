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

namespace Xima\XimaTypo3FrontendEdit\Service\Menu;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;
use Xima\XimaTypo3FrontendEdit\Utility\StringUtility;

/**
 * MenuButtonBuilder.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class MenuButtonBuilder
{
    public function __construct(
        private IconService $iconService,
        private UrlBuilderService $urlBuilderService,
    ) {}

    /**
     * @param array<string, mixed> $contentElement
     *
     * @throws RouteNotFoundException
     */
    public function createSimpleEditButton(
        array $contentElement,
        int $languageUid,
        string $returnUrlAnchor,
        bool $linkTargetBlank,
    ): Button {
        $url = $this->urlBuilderService->buildEditUrl(
            $contentElement['uid'],
            'tt_content',
            $languageUid,
            $returnUrlAnchor,
        ).'&tx_ximatypo3frontendedit';

        return new Button(
            'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:edit_menu',
            ButtonType::Link,
            $url,
            $this->iconService->getIcon('actions-open'),
            $linkTargetBlank,
        );
    }

    public function createFullMenuButton(): Button
    {
        return new Button(
            'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:edit_menu',
            ButtonType::Menu,
            icon: $this->iconService->getIcon('actions-open'),
        );
    }

    /**
     * @param array<string, mixed> $contentElement
     * @param array<string, mixed> $contentElementConfig
     */
    public function addInfoSection(
        Button $menuButton,
        array $contentElement,
        array $contentElementConfig,
    ): void {
        $this->addButton($menuButton, 'div_info', ButtonType::Divider);

        $additionalUid = $GLOBALS['BE_USER']->isAdmin()
            ? ' <code>['.$contentElement['uid'].']</code>'
            : '';

        $label = $GLOBALS['LANG']->sL($contentElementConfig['label']).
            '<p><small>'.
            (null !== $contentElement['header'] ? StringUtility::shortenString($contentElement['header']) : '').
            $additionalUid.
            '</small></p>';

        $this->addButton(
            $menuButton,
            'header',
            ButtonType::Info,
            $label,
            icon: $contentElementConfig['icon'],
        );
    }

    /**
     * @param array<string, mixed> $contentElement
     *
     * @throws RouteNotFoundException
     */
    public function addEditSection(
        Button $menuButton,
        array $contentElement,
        int $languageUid,
        int $pid,
        string $returnUrlAnchor,
    ): void {
        $this->addButton($menuButton, 'div_edit', ButtonType::Divider);

        // Edit content element button
        $editLabel = 'list' === $contentElement['CType']
            ? 'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:edit_plugin'
            : 'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:edit_content_element';

        $editUrl = $this->urlBuilderService->buildEditUrl(
            $contentElement['uid'],
            'tt_content',
            $languageUid,
            $returnUrlAnchor,
        ).'&tx_ximatypo3frontendedit';

        $editIcon = 'list' === $contentElement['CType'] ? 'content-plugin' : 'content-textpic';

        $this->addButton($menuButton, 'edit', ButtonType::Link, $editLabel, $editUrl, $editIcon);

        // Edit page button
        $pageUrl = $this->urlBuilderService->buildPageLayoutUrl($pid, $languageUid, $returnUrlAnchor);
        $this->addButton($menuButton, 'edit_page', ButtonType::Link, url: $pageUrl, icon: 'apps-pagetree-page-default');
    }

    /**
     * @param array<string, mixed> $contentElement
     *
     * @throws RouteNotFoundException
     */
    public function addActionSection(
        Button $menuButton,
        array $contentElement,
        string $returnUrlAnchor,
    ): void {
        $this->addButton($menuButton, 'div_action', ButtonType::Divider);

        // Hide button
        $hideUrl = $this->urlBuilderService->buildHideUrl($contentElement['uid'], $returnUrlAnchor);
        $this->addButton($menuButton, 'hide', ButtonType::Link, url: $hideUrl, icon: 'actions-toggle-on');

        // Info button
        $infoUrl = $this->urlBuilderService->buildInfoUrl($contentElement['uid'], 'tt_content', $returnUrlAnchor);
        $this->addButton($menuButton, 'info', ButtonType::Link, url: $infoUrl, icon: 'actions-info');

        // Move button
        $moveUrl = $this->urlBuilderService->buildMoveUrl(
            $contentElement['uid'],
            'tt_content',
            (int) $contentElement['pid'],
            $returnUrlAnchor,
        );
        $this->addButton($menuButton, 'move', ButtonType::Link, url: $moveUrl, icon: 'actions-move');

        // History button
        $historyUrl = $this->urlBuilderService->buildHistoryUrl($contentElement['uid'], 'tt_content', $returnUrlAnchor);
        $this->addButton($menuButton, 'history', ButtonType::Link, url: $historyUrl, icon: 'actions-history');
    }

    /**
     * Add page edit section (for page dropdown in sticky toolbar).
     */
    public function addPageEditSection(
        Button $menuButton,
        int $pageId,
        int $languageUid,
        string $returnUrl,
    ): void {
        $this->addButton($menuButton, 'div_edit', ButtonType::Divider);

        // Edit page properties
        $editUrl = $this->urlBuilderService->buildEditUrl($pageId, 'pages', $languageUid, $returnUrl);
        $this->addButton($menuButton, 'edit_page_properties', ButtonType::Link, url: $editUrl, icon: 'actions-page-open');

        // Edit page (Backend Page Layout)
        $pageUrl = $this->urlBuilderService->buildPageLayoutUrl($pageId, $languageUid, $returnUrl);
        $this->addButton($menuButton, 'edit_page', ButtonType::Link, url: $pageUrl, icon: 'apps-pagetree-page-default');
    }

    /**
     * Add page action section (for page dropdown in sticky toolbar).
     */
    public function addPageActionSection(
        Button $menuButton,
        int $pageId,
        string $returnUrl,
    ): void {
        $this->addButton($menuButton, 'div_action', ButtonType::Divider);

        // Page info
        $infoUrl = $this->urlBuilderService->buildInfoUrl($pageId, 'pages', $returnUrl);
        $this->addButton($menuButton, 'info', ButtonType::Link, url: $infoUrl, icon: 'actions-info');

        // Page history
        $historyUrl = $this->urlBuilderService->buildHistoryUrl($pageId, 'pages', $returnUrl);
        $this->addButton($menuButton, 'history', ButtonType::Link, url: $historyUrl, icon: 'actions-history');
    }

    public function addButton(
        Button $menuButton,
        string $identifier,
        ButtonType $type,
        ?string $label = null,
        ?string $url = null,
        ?string $icon = null,
    ): void {
        $finalLabel = $label ?? 'LLL:EXT:'.Configuration::EXT_KEY."/Resources/Private/Language/locallang.xlf:$identifier";
        $finalIcon = null !== $icon ? $this->iconService->getIcon($icon) : null;

        $button = new Button(
            $finalLabel,
            $type,
            $url,
            $finalIcon,
            false, // Target blank will be handled at a higher level
        );

        $menuButton->appendChild($button, $identifier);
    }
}
