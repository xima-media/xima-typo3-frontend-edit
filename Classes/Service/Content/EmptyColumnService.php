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

namespace Xima\XimaTypo3FrontendEdit\Service\Content;

use Throwable;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Service\Ui\UrlBuilderService;

use function is_array;

/**
 * EmptyColumnService.
 *
 * @license GPL-2.0-or-later
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final readonly class EmptyColumnService
{
    private bool $containerFieldExists;

    public function __construct(
        private ConnectionPool $connectionPool,
        private UrlBuilderService $urlBuilderService,
        private LanguageServiceFactory $languageServiceFactory,
    ) {
        $this->containerFieldExists = $this->detectContainerField();
    }

    /**
     * @param int          $pid               Page UID
     * @param int          $languageUid       sys_language_uid
     * @param string       $returnUrl         Frontend URL to return to after editing
     * @param array<mixed> $requestData       Raw request data from the AJAX call
     * @param bool         $showInsertButtons Whether per-element "insert after" buttons are enabled;
     *                                        if so, the end-of-column button on filled columns is redundant
     *
     * @return list<array{colPos: int, isEmpty: bool, newContentUrl: string, name?: string, containerUid?: int}>
     */
    public function getColumnTargets(int $pid, int $languageUid, string $returnUrl, array $requestData = [], bool $showInsertButtons = false): array
    {
        $targets = [
            ...$this->findPageColumnTargets($pid, $languageUid, $returnUrl),
            ...$this->findContainerColumnTargets($pid, $languageUid, $returnUrl, $this->extractContainerMarkers($requestData)),
        ];

        // Filled columns already expose an "insert after" button on their last element,
        // so the standalone end-of-column button would only duplicate it. Empty columns
        // have no elements and therefore keep their button.
        if ($showInsertButtons) {
            $targets = array_values(array_filter($targets, static fn (array $target): bool => $target['isEmpty']));
        }

        return $targets;
    }

    /**
     * @return list<array{colPos: int, isEmpty: bool, name: string, newContentUrl: string}>
     */
    private function findPageColumnTargets(int $pid, int $languageUid, string $returnUrl): array
    {
        $result = [];
        $counts = $this->countPageColumnContent($pid, $languageUid);

        foreach ($this->resolvePageColumns($pid) as $column) {
            $colPos = $column['colPos'];
            try {
                $result[] = [
                    'colPos' => $colPos,
                    'isEmpty' => 0 === ($counts[$colPos] ?? 0),
                    'name' => $column['name'],
                    'newContentUrl' => $this->urlBuilderService->buildNewContentWizardUrl($pid, $colPos, $languageUid, $returnUrl),
                ];
            } catch (RouteNotFoundException) {
            }
        }

        return $result;
    }

    /**
     * @param array<int, int[]> $containerMarkers
     *
     * @return list<array{colPos: int, isEmpty: bool, containerUid: int, newContentUrl: string}>
     */
    private function findContainerColumnTargets(int $pid, int $languageUid, string $returnUrl, array $containerMarkers): array
    {
        if (!$this->containerFieldExists || [] === $containerMarkers) {
            return [];
        }

        $counts = $this->countContainerColumnContent(
            array_keys($containerMarkers),
            array_values(array_unique(array_merge(...array_values($containerMarkers)))),
            $languageUid,
        );

        $result = [];

        foreach ($containerMarkers as $containerUid => $colPositions) {
            if ($containerUid <= 0) {
                continue;
            }
            foreach ($colPositions as $colPos) {
                try {
                    $result[] = [
                        'colPos' => $colPos,
                        'isEmpty' => 0 === ($counts[$containerUid][$colPos] ?? 0),
                        'containerUid' => $containerUid,
                        'newContentUrl' => $this->urlBuilderService->buildNewContentWizardUrl($pid, $colPos, $languageUid, $returnUrl, containerUid: $containerUid),
                    ];
                } catch (RouteNotFoundException) {
                }
            }
        }

        return $result;
    }

    /**
     * @return list<array{colPos: int, name: string}>
     */
    private function resolvePageColumns(int $pid): array
    {
        $default = [['colPos' => 0, 'name' => 'Content']];

        try {
            $backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
            $backendLayout = $backendLayoutView->getBackendLayoutForPage($pid);

            if (null === $backendLayout) {
                return $default;
            }

            $columns = $this->extractColumnsFromStructure($backendLayout->getStructure());

            if ([] === $columns) {
                foreach ($backendLayout->getUsedColumns() as $colPos => $name) {
                    $columns[] = [
                        'colPos' => (int) $colPos,
                        'name' => $this->translateLabel($name ?: 'Column '.$colPos),
                    ];
                }
            }

            return [] !== $columns ? $columns : $default;
        } catch (Throwable) {
            return $default;
        }
    }

    /**
     * @param array<string, mixed> $structure
     *
     * @return list<array{colPos: int, name: string}>
     */
    private function extractColumnsFromStructure(array $structure): array
    {
        $columns = [];

        foreach ($structure['__config']['backend_layout.']['rows.'] ?? [] as $row) {
            foreach ($row['columns.'] ?? [] as $column) {
                if (!isset($column['colPos'])) {
                    continue;
                }
                $columns[] = [
                    'colPos' => (int) $column['colPos'],
                    'name' => $this->translateLabel($column['name'] ?? 'Column '.$column['colPos']),
                ];
            }
        }

        return $columns;
    }

    /**
     * Count visible content elements per column of a page in a single grouped
     * query, so column emptiness can be resolved without one COUNT query per column.
     *
     * @return array<int, int> colPos => number of visible content elements
     */
    private function countPageColumnContent(int $pid, int $languageUid): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');

        $conditions = [
            $qb->expr()->eq('pid', $qb->createNamedParameter($pid, Connection::PARAM_INT)),
            $qb->expr()->in('sys_language_uid', [
                $qb->createNamedParameter($languageUid, Connection::PARAM_INT),
                $qb->createNamedParameter(-1, Connection::PARAM_INT),
            ]),
            $qb->expr()->eq('deleted', $qb->createNamedParameter(0, Connection::PARAM_INT)),
            $qb->expr()->eq('hidden', $qb->createNamedParameter(0, Connection::PARAM_INT)),
        ];

        if ($this->containerFieldExists) {
            $conditions[] = $qb->expr()->eq('tx_container_parent', $qb->createNamedParameter(0, Connection::PARAM_INT));
        }

        $rows = $qb
            ->select('colPos')
            ->addSelectLiteral($qb->expr()->count('uid', 'cnt'))
            ->from('tt_content')
            ->where(...$conditions)
            ->groupBy('colPos')
            ->executeQuery()
            ->fetchAllAssociative();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['colPos']] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * Count visible content elements per (container, column) in a single grouped
     * query, so container column emptiness can be resolved without one COUNT query
     * per column.
     *
     * @param int[] $containerUids
     * @param int[] $colPositions
     *
     * @return array<int, array<int, int>> containerUid => (colPos => count)
     */
    private function countContainerColumnContent(array $containerUids, array $colPositions, int $languageUid): array
    {
        if ([] === $containerUids || [] === $colPositions) {
            return [];
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');

        $rows = $qb
            ->select('tx_container_parent', 'colPos')
            ->addSelectLiteral($qb->expr()->count('uid', 'cnt'))
            ->from('tt_content')
            ->where(
                $qb->expr()->in('tx_container_parent', $qb->createNamedParameter($containerUids, Connection::PARAM_INT_ARRAY)),
                $qb->expr()->in('colPos', $qb->createNamedParameter($colPositions, Connection::PARAM_INT_ARRAY)),
                $qb->expr()->in('sys_language_uid', [
                    $qb->createNamedParameter($languageUid, Connection::PARAM_INT),
                    $qb->createNamedParameter(-1, Connection::PARAM_INT),
                ]),
                $qb->expr()->eq('deleted', $qb->createNamedParameter(0, Connection::PARAM_INT)),
                $qb->expr()->eq('hidden', $qb->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->groupBy('tx_container_parent', 'colPos')
            ->executeQuery()
            ->fetchAllAssociative();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['tx_container_parent']][(int) $row['colPos']] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * @param array<mixed> $requestData
     *
     * @return array<int, int[]>
     */
    private function extractContainerMarkers(array $requestData): array
    {
        $markers = [];

        foreach ((array) ($requestData['_containerMarkers'] ?? []) as $uid => $colPositions) {
            $uid = (int) $uid;
            if ($uid > 0 && is_array($colPositions)) {
                $markers[$uid] = array_filter(array_map(intval(...), $colPositions), static fn (int $v): bool => $v > 0);
            }
        }

        return $markers;
    }

    private function detectContainerField(): bool
    {
        // EXT:container registers tx_container_parent in the TCA, which is already
        // loaded in memory — cheaper than introspecting the database schema on every
        // request just to learn whether the column exists.
        return isset($GLOBALS['TCA']['tt_content']['columns']['tx_container_parent']);
    }

    private function translateLabel(string $label): string
    {
        if (!str_starts_with($label, 'LLL:')) {
            return $label;
        }

        try {
            if (isset($GLOBALS['BE_USER'])) {
                return $this->languageServiceFactory
                    ->createFromUserPreferences($GLOBALS['BE_USER'])
                    ->sL($label) ?: $label;
            }
        } catch (Throwable) {
        }

        return $label;
    }
}
