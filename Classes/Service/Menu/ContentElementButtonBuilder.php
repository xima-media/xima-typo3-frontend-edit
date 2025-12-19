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
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;
use Xima\XimaTypo3FrontendEdit\Utility\StringUtility;

/**
 * ContentElementButtonBuilder.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class ContentElementButtonBuilder extends AbstractMenuButtonBuilder
{
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
     * Add info section with header, type, and ID for content elements.
     *
     * @param array<string, mixed> $contentElement
     * @param array<string, mixed> $contentElementConfig
     */
    public function addInfoSection(
        Button $menuButton,
        array $contentElement,
        array $contentElementConfig,
    ): void {
        $this->addButton($menuButton, 'div_info', ButtonType::Divider);

        $typeLabel = $GLOBALS['LANG']->sL($contentElementConfig['label'] ?? '') ?: $contentElement['CType'];
        $header = isset($contentElement['header']) && '' !== $contentElement['header']
            ? htmlspecialchars(StringUtility::shortenString((string) $contentElement['header']), \ENT_QUOTES, 'UTF-8')
            : '';
        $uid = (int) $contentElement['uid'];

        $label = $typeLabel
            .('' !== $header ? '<br><small>'.$header.'</small>' : '')
            .'<code>[uid: '.$uid.']</code>';

        $iconIdentifier = $contentElementConfig['icon'] ?? 'content-textpic';

        $this->addButton(
            $menuButton,
            'info_header',
            ButtonType::Info,
            $label,
            icon: $iconIdentifier,
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

        // Edit page button (with anchor to scroll to content element)
        $pageUrl = $this->urlBuilderService->buildPageLayoutUrl(
            $pid,
            $languageUid,
            $returnUrlAnchor,
            (int) $contentElement['uid'],
        );
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

        // New content after button
        $newContentUrl = $this->urlBuilderService->buildNewContentAfterUrl($contentElement['uid'], $returnUrlAnchor);
        $this->addButton($menuButton, 'new_content_after', ButtonType::Link, url: $newContentUrl, icon: 'actions-add');
    }
}
