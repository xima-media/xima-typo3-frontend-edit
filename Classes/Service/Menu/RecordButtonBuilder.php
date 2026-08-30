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
 * RecordButtonBuilder.
 *
 * Builds the deliberately thin edit+info+history menu for a foreign (non
 * tt_content, non pages) record - see issue #216. No hide/delete/move/insert:
 * their semantics are table-specific and out of scope for a generic record.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class RecordButtonBuilder extends AbstractMenuButtonBuilder
{
    /**
     * @param array<string, mixed> $record
     */
    public function addInfoSection(
        Button $menuButton,
        string $table,
        array $record,
    ): void {
        $this->addButton($menuButton, 'div_info', ButtonType::Divider);

        $titleField = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? '';
        $title = '' !== $titleField && isset($record[$titleField]) && '' !== (string) $record[$titleField]
            ? htmlspecialchars(StringUtility::shortenString((string) $record[$titleField]), \ENT_QUOTES, 'UTF-8')
            : '';
        $uid = (int) $record['uid'];

        $tableLabel = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title'] ?? '') ?: $table;

        $label = $tableLabel
            .('' !== $title ? '<br><small>'.$title.'</small>' : '')
            .'<code>[uid: '.$uid.']</code>';

        $iconIdentifier = $this->iconService->getIconIdentifierForRecord($table, $record);

        $this->addButton(
            $menuButton,
            'info_header',
            ButtonType::Info,
            $label,
            null,
            $iconIdentifier,
        );
    }

    /**
     * @throws RouteNotFoundException
     */
    public function addEditSection(
        Button $menuButton,
        string $table,
        int $uid,
        int $languageUid,
        string $returnUrl,
        ?string $contextualUrl = null,
    ): void {
        $this->addButton($menuButton, 'div_edit', ButtonType::Divider);

        $editUrl = $this->urlBuilderService->buildEditUrl($uid, $table, $languageUid, $returnUrl);
        $this->addButton($menuButton, 'edit', ButtonType::Link, null, $editUrl, 'actions-open');

        if (null !== $contextualUrl) {
            $menuButton->getChildren()['edit']->setContextualUrl($contextualUrl);
        }
    }

    /**
     * @throws RouteNotFoundException
     */
    public function addActionSection(
        Button $menuButton,
        string $table,
        int $uid,
        string $returnUrl,
    ): void {
        $this->addButton($menuButton, 'div_action', ButtonType::Divider);

        $infoUrl = $this->urlBuilderService->buildInfoUrl($uid, $table, $returnUrl);
        $this->addButton($menuButton, 'info', ButtonType::Link, null, $infoUrl, 'actions-info');

        $historyUrl = $this->urlBuilderService->buildHistoryUrl($uid, $table, $returnUrl);
        $this->addButton($menuButton, 'history', ButtonType::Link, null, $historyUrl, 'actions-history');
    }
}
