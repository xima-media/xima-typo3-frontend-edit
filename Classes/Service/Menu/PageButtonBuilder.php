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

namespace Xima\XimaTypo3FrontendEdit\Service\Menu;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;
use Xima\XimaTypo3FrontendEdit\Utility\StringUtility;

/**
 * PageButtonBuilder.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class PageButtonBuilder extends AbstractMenuButtonBuilder
{
    /**
     * Add info section with title, type, and ID for pages.
     *
     * @param array<string, mixed> $pageRecord
     */
    public function addInfoSection(
        Button $menuButton,
        array $pageRecord,
    ): void {
        $this->addButton($menuButton, 'div_info', ButtonType::Divider);

        $title = isset($pageRecord['title']) && '' !== $pageRecord['title']
            ? htmlspecialchars(StringUtility::shortenString((string) $pageRecord['title']), \ENT_QUOTES, 'UTF-8')
            : '';
        $doktype = (int) ($pageRecord['doktype'] ?? 1);
        $uid = (int) $pageRecord['uid'];

        $doktypeLabel = $this->getDoktypeLabel($doktype);
        $iconIdentifier = $this->getDoktypeIcon($doktype);

        $label = $doktypeLabel
            .('' !== $title ? '<br><small>'.$title.'</small>' : '')
            .'<code>[uid: '.$uid.']</code>';

        $this->addButton(
            $menuButton,
            'info_header',
            ButtonType::Info,
            $label,
            icon: $iconIdentifier,
        );
    }

    /**
     * Add page edit section (for page dropdown in sticky toolbar).
     *
     * @throws RouteNotFoundException
     */
    public function addEditSection(
        Button $menuButton,
        int $pageId,
        int $languageUid,
        string $returnUrl,
    ): void {
        $this->addButton($menuButton, 'div_edit', ButtonType::Divider);

        $editUrl = $this->urlBuilderService->buildEditUrl($pageId, 'pages', $languageUid, $returnUrl).'&tx_ximatypo3frontendedit';
        $this->addButton($menuButton, 'edit_page_properties', ButtonType::Link, url: $editUrl, icon: 'actions-page-open');

        // Edit page (Backend Page Layout)
        $pageUrl = $this->urlBuilderService->buildPageLayoutUrl($pageId, $languageUid, $returnUrl);
        $this->addButton($menuButton, 'edit_page', ButtonType::Link, url: $pageUrl, icon: 'apps-pagetree-page-default');
    }

    /**
     * Add page action section (for page dropdown in sticky toolbar).
     *
     * @throws RouteNotFoundException
     */
    public function addActionSection(
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

    /**
     * Get doktype label dynamically from TCA.
     */
    private function getDoktypeLabel(int $doktype): string
    {
        $items = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] ?? [];

        foreach ($items as $item) {
            if (isset($item['value']) && (int) $item['value'] === $doktype) {
                return $GLOBALS['LANG']->sL($item['label'] ?? '') ?: 'Page';
            }
        }

        return 'Page';
    }

    /**
     * Get doktype icon dynamically from TCA.
     */
    private function getDoktypeIcon(int $doktype): string
    {
        $typeIconClasses = $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'] ?? [];

        return $typeIconClasses[$doktype] ?? 'apps-pagetree-page-default';
    }
}
