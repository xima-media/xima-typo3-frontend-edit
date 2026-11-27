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

use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;

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
 * @phpstan-type RecordLookup array{requested: array<string, array<int, array<string, mixed>>>, default: array<string, array<int, array<string, mixed>>>, translated: array<string, array<int, array<string, mixed>>>}
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
        $references = array_values(array_filter(
            $references,
            static fn (array $reference): bool => isset($GLOBALS['TCA'][$reference['table']]),
        ));
        if ([] === $references) {
            return [];
        }

        $records = $this->loadRecords($references, $languageUid);
        $contextualEditAvailable = $this->urlBuilderService->isContextualEditRouteAvailable();

        $result = [];
        foreach ($references as $reference) {
            $menuData = $this->buildMenuForReference($reference['table'], $reference['uid'], $languageUid, $returnUrl, $records, $contextualEditAvailable);
            if (null === $menuData) {
                continue;
            }

            $result[$reference['table'].':'.$reference['uid']] = $menuData;
        }

        return $result;
    }

    /**
     * Loads everything the menus need with a constant number of queries per table:
     * the requested records, the default-language records of requested translations
     * and the translations into the requested language.
     *
     * @param array<int, array{table: string, uid: int}> $references
     *
     * @return RecordLookup
     */
    private function loadRecords(array $references, int $languageUid): array
    {
        $uidsByTable = [];
        foreach ($references as $reference) {
            $uidsByTable[$reference['table']][] = $reference['uid'];
        }

        $records = ['requested' => [], 'default' => [], 'translated' => []];
        foreach ($uidsByTable as $table => $uids) {
            $requested = $this->contentElementRepository->fetchRecordsByUids($table, array_values(array_unique($uids)));
            $defaultUids = array_values(array_unique(array_map(
                fn (array $record): int => $this->getDefaultLanguageUid($table, $record),
                $requested,
            )));

            $records['requested'][$table] = $requested;
            $records['default'][$table] = $requested + $this->contentElementRepository->fetchRecordsByUids(
                $table,
                array_values(array_diff($defaultUids, array_keys($requested))),
            );
            $records['translated'][$table] = $languageUid > 0
                ? $this->contentElementRepository->getTranslatedRecords($table, $defaultUids, $languageUid)
                : [];
        }

        return $records;
    }

    /**
     * @param RecordLookup $records
     *
     * @return array{element: array<string, mixed>, menu: array<string, mixed>}|null
     */
    private function buildMenuForReference(string $table, int $uid, int $languageUid, string $returnUrl, array $records, bool $contextualEditAvailable): ?array
    {
        $requestedRecord = $records['requested'][$table][$uid] ?? null;
        if (null === $requestedRecord) {
            return null;
        }

        $targetRecord = $this->resolveTargetRecord($table, $requestedRecord, $languageUid, $records);
        if (!$this->backendUserService->hasRecordEditAccess($table, $targetRecord)) {
            return null;
        }

        $targetUid = (int) $targetRecord['uid'];
        $contextualUrl = $contextualEditAvailable
            ? $this->urlBuilderService->buildContextualEditUrl($targetUid, $table, $languageUid, $returnUrl)
            : null;

        $menuButton = new Button(
            'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang.xlf:record_menu',
            ButtonType::Menu,
            null,
            null,
            false,
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
     * @param RecordLookup         $records
     *
     * @return array<string, mixed>
     */
    private function resolveTargetRecord(string $table, array $requestedRecord, int $languageUid, array $records): array
    {
        $defaultUid = $this->getDefaultLanguageUid($table, $requestedRecord);
        $defaultRecord = (int) ($requestedRecord['sys_language_uid'] ?? 0) > 0
            ? ($records['default'][$table][$defaultUid] ?? $requestedRecord)
            : $requestedRecord;

        if ($languageUid <= 0) {
            return $defaultRecord;
        }

        return $records['translated'][$table][$defaultUid] ?? $defaultRecord;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function getDefaultLanguageUid(string $table, array $record): int
    {
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? 'l10n_parent';
        $defaultUid = (int) ($record['sys_language_uid'] ?? 0) > 0 ? (int) ($record[$transOrigPointerField] ?? 0) : (int) $record['uid'];

        return $defaultUid > 0 ? $defaultUid : (int) $record['uid'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
