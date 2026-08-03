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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Localization\LanguageService;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

use function is_array;

/**
 * RecordMenuGenerator.
 *
 * Generates the deliberately thin edit+info+history menu for foreign
 * (non tt_content, non pages) records matched via the
 * data-frontend-edit="{table}:{uid}" attribute - see issue #216. No
 * TCA-generic framework: permissions reuse the existing table-agnostic
 * BackendUserService::hasRecordEditAccess() path, and extensibility reuses
 * the existing FrontendEditDropdownModifyEvent (the record row carries a
 * `_table` marker so listeners can tell it apart from a tt_content row).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class RecordMenuGenerator extends AbstractMenuGenerator
{
    public function __construct(
        private readonly RecordButtonBuilder $recordButtonBuilder,
        private readonly EventDispatcher $eventDispatcher,
        private readonly BackendUserService $backendUserService,
        private readonly ContentElementRepository $contentElementRepository,
        private readonly UrlBuilderService $urlBuilderService,
        private readonly IconService $iconService,
        ExtensionConfiguration $extensionConfiguration,
    ) {
        parent::__construct($extensionConfiguration);
    }

    /**
     * @param array<int, array{table: string, uid: int}> $references
     *
     * @return array<string, array{element: array<string, mixed>, menu: array<string, mixed>}>
     *
     * @throws RouteNotFoundException
     */
    public function getDropdown(array $references, int $languageUid, string $returnUrl): array
    {
        $result = [];
        foreach ($references as $reference) {
            $menuData = $this->buildMenuForReference($reference['table'], $reference['uid'], $languageUid, $returnUrl);
            if (null === $menuData) {
                continue;
            }

            $result[$reference['table'].':'.$reference['uid']] = $menuData;
        }

        return $result;
    }

    /**
     * @return array{element: array<string, mixed>, menu: array<string, mixed>}|null
     *
     * @throws RouteNotFoundException
     */
    private function buildMenuForReference(string $table, int $uid, int $languageUid, string $returnUrl): ?array
    {
        if (!isset($GLOBALS['TCA'][$table])) {
            return null;
        }

        $requestedRecord = BackendUtility::getRecord($table, $uid);
        if (null === $requestedRecord) {
            return null;
        }

        $targetRecord = $this->resolveTargetRecord($table, $requestedRecord, $languageUid);
        if (!$this->backendUserService->hasRecordEditAccess($table, $targetRecord)) {
            return null;
        }

        $targetUid = (int) $targetRecord['uid'];
        $contextualUrl = $this->urlBuilderService->isContextualEditRouteAvailable()
            ? $this->urlBuilderService->buildContextualEditUrl($targetUid, $table, $languageUid, $returnUrl)
            : null;

        $menuButton = new Button(
            'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:record_menu',
            ButtonType::Menu,
        );
        $this->recordButtonBuilder->addInfoSection($menuButton, $table, $targetRecord);
        $this->recordButtonBuilder->addEditSection($menuButton, $table, $targetUid, $languageUid, $returnUrl, $contextualUrl);
        $this->recordButtonBuilder->addActionSection($menuButton, $table, $targetUid, $returnUrl);

        // Same keys ContentElementMenuGenerator sets for tt_content, so the
        // frontend's existing generic toolbar-label rendering (which reads
        // element.ctypeLabel/ctypeIcon, falling back to a plain "Content"
        // label when neither exists) shows the record's own table/type
        // instead of that generic fallback.
        $element = $targetRecord;
        $element['_table'] = $table;
        $element['ctypeLabel'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title'] ?? '') ?: $table;
        $element['ctypeIcon'] = (string) $this->iconService->getIcon($this->iconService->getIconIdentifierForRecord($table, $targetRecord));

        /** @var FrontendEditDropdownModifyEvent $event */
        $event = $this->eventDispatcher->dispatch(new FrontendEditDropdownModifyEvent($element, $menuButton, $returnUrl));

        return [
            'element' => $element,
            'menu' => $event->getMenuButton()->render(),
        ];
    }

    /**
     * Resolves the record the menu should target: the requested-language
     * translation when available, otherwise the default-language record
     * (analogous to tt_content's fallback rendering - never the requested
     * record verbatim when it is itself in neither language).
     *
     * @param array<string, mixed> $requestedRecord
     *
     * @return array<string, mixed>
     */
    private function resolveTargetRecord(string $table, array $requestedRecord, int $languageUid): array
    {
        $recordLanguage = (int) ($requestedRecord['sys_language_uid'] ?? 0);
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? 'l10n_parent';
        $defaultUid = $recordLanguage > 0 ? (int) ($requestedRecord[$transOrigPointerField] ?? 0) : (int) $requestedRecord['uid'];
        if ($defaultUid <= 0) {
            $defaultUid = (int) $requestedRecord['uid'];
        }

        $defaultRecord = $recordLanguage > 0
            ? (BackendUtility::getRecord($table, $defaultUid) ?? $requestedRecord)
            : $requestedRecord;

        if ($languageUid <= 0) {
            return $defaultRecord;
        }

        $translated = $this->contentElementRepository->getTranslatedRecord($table, $defaultUid, $languageUid);

        return is_array($translated) ? $translated : $defaultRecord;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
