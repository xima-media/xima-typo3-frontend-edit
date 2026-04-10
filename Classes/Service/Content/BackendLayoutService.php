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

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to retrieve backend layout column information for a page.
 *
 * Used by SingleColumnButtonViewHelper to determine whether a column
 * is empty and should display a "Create new content" button.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class BackendLayoutService
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Get the backend layout columns for a page with their content count.
     *
     * @return array<int, array{colPos: int, name: string, contentCount: int}>
     */
    public function getColumnsForPage(int $pageId, int $languageUid = 0): array
    {
        $columns = [];

        try {
            $backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
            $backendLayout = $backendLayoutView->getBackendLayoutForPage($pageId);

            if (null === $backendLayout) {
                return [
                    [
                        'colPos' => 0,
                        'name' => 'Content',
                        'contentCount' => $this->getContentCountForColumn($pageId, 0, $languageUid),
                    ],
                ];
            }

            $structure = $backendLayout->getStructure();

            if (isset($structure['__config']['backend_layout.']['rows.'])) {
                foreach ($structure['__config']['backend_layout.']['rows.'] as $row) {
                    if (!isset($row['columns.'])) {
                        continue;
                    }
                    foreach ($row['columns.'] as $column) {
                        if (!isset($column['colPos'])) {
                            continue;
                        }
                        $colPos = (int) $column['colPos'];
                        $columns[] = [
                            'colPos' => $colPos,
                            'name' => $this->translateLabel($column['name'] ?? 'Column '.$colPos),
                            'contentCount' => $this->getContentCountForColumn($pageId, $colPos, $languageUid),
                        ];
                    }
                }
            }

            if ([] === $columns && method_exists($backendLayout, 'getUsedColumns')) {
                foreach ($backendLayout->getUsedColumns() as $colPos => $columnName) {
                    $columns[] = [
                        'colPos' => (int) $colPos,
                        'name' => $this->translateLabel($columnName ?: 'Column '.$colPos),
                        'contentCount' => $this->getContentCountForColumn($pageId, (int) $colPos, $languageUid),
                    ];
                }
            }

            if ([] === $columns) {
                $columns = [
                    [
                        'colPos' => 0,
                        'name' => 'Content',
                        'contentCount' => $this->getContentCountForColumn($pageId, 0, $languageUid),
                    ],
                ];
            }
        } catch (\Throwable) {
            $columns = [
                [
                    'colPos' => 0,
                    'name' => 'Content',
                    'contentCount' => $this->getContentCountForColumn($pageId, 0, $languageUid),
                ],
            ];
        }

        return $columns;
    }

    /**
     * Get the count of non-deleted content elements in a specific column.
     */
    public function getContentCountForColumn(int $pageId, int $colPos, int $languageUid = 0): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

        return (int) $queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter($colPos, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('deleted', 0),
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function translateLabel(string $label): string
    {
        if (!str_starts_with($label, 'LLL:')) {
            return $label;
        }

        try {
            if (isset($GLOBALS['BE_USER']) && null !== $GLOBALS['BE_USER']) {
                $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
                $languageService = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);

                return $languageService->sL($label) ?: $label;
            }
        } catch (\Throwable) {
            // Fallback to original label
        }

        return $label;
    }
}