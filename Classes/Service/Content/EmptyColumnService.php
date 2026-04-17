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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        private UriBuilder $uriBuilder,
        private LanguageServiceFactory $languageServiceFactory,
    ) {
        $this->containerFieldExists = $this->detectContainerField();
    }

    /**
     * @param int          $pid         Page UID
     * @param int          $languageUid sys_language_uid
     * @param string       $returnUrl   Frontend URL to return to after editing
     * @param array<mixed> $requestData Raw request data from the AJAX call
     *
     * @return list<array{colPos: int, newContentUrl: string, name?: string, containerUid?: int}>
     */
    public function getEmptyColumns(int $pid, int $languageUid, string $returnUrl, array $requestData = []): array
    {
        return [
            ...$this->findEmptyPageColumns($pid, $languageUid, $returnUrl),
            ...$this->findEmptyContainerColumns($pid, $languageUid, $returnUrl, $this->extractContainerMarkers($requestData)),
        ];
    }

    /**
     * @return list<array{colPos: int, name: string, newContentUrl: string}>
     */
    private function findEmptyPageColumns(int $pid, int $languageUid, string $returnUrl): array
    {
        $result = [];

        foreach ($this->resolvePageColumns($pid) as $column) {
            $colPos = $column['colPos'];
            if (!$this->isPageColumnEmpty($pid, $colPos, $languageUid)) {
                continue;
            }
            try {
                $result[] = [
                    'colPos' => $colPos,
                    'name' => $column['name'],
                    'newContentUrl' => $this->buildNewContentUrl($pid, $colPos, $languageUid, $returnUrl),
                ];
            } catch (RouteNotFoundException) {
            }
        }

        return $result;
    }

    /**
     * @param array<int, int[]> $containerMarkers
     *
     * @return list<array{colPos: int, containerUid: int, newContentUrl: string}>
     */
    private function findEmptyContainerColumns(int $pid, int $languageUid, string $returnUrl, array $containerMarkers): array
    {
        if (!$this->containerFieldExists || [] === $containerMarkers) {
            return [];
        }

        $result = [];

        foreach ($containerMarkers as $containerUid => $colPositions) {
            if ($containerUid <= 0) {
                continue;
            }
            foreach ($colPositions as $colPos) {
                if (!$this->isContainerColumnEmpty($containerUid, $colPos, $languageUid)) {
                    continue;
                }
                try {
                    $result[] = [
                        'colPos' => $colPos,
                        'containerUid' => $containerUid,
                        'newContentUrl' => $this->buildNewContentUrl($pid, $colPos, $languageUid, $returnUrl, $containerUid),
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

    private function isPageColumnEmpty(int $pid, int $colPos, int $languageUid): bool
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');

        $conditions = [
            $qb->expr()->eq('pid', $qb->createNamedParameter($pid, Connection::PARAM_INT)),
            $qb->expr()->eq('colPos', $qb->createNamedParameter($colPos, Connection::PARAM_INT)),
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

        return 0 === (int) $qb->count('uid')->from('tt_content')->where(...$conditions)->executeQuery()->fetchOne();
    }

    private function isContainerColumnEmpty(int $containerUid, int $colPos, int $languageUid): bool
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');

        return 0 === (int) $qb
            ->count('uid')
            ->from('tt_content')
            ->where(
                $qb->expr()->eq('tx_container_parent', $qb->createNamedParameter($containerUid, Connection::PARAM_INT)),
                $qb->expr()->eq('colPos', $qb->createNamedParameter($colPos, Connection::PARAM_INT)),
                $qb->expr()->in('sys_language_uid', [
                    $qb->createNamedParameter($languageUid, Connection::PARAM_INT),
                    $qb->createNamedParameter(-1, Connection::PARAM_INT),
                ]),
                $qb->expr()->eq('deleted', $qb->createNamedParameter(0, Connection::PARAM_INT)),
                $qb->expr()->eq('hidden', $qb->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @throws RouteNotFoundException
     */
    private function buildNewContentUrl(int $pid, int $colPos, int $languageUid, string $returnUrl, ?int $containerUid = null): string
    {
        $defVals = [
            'colPos' => $colPos,
            'sys_language_uid' => $languageUid,
        ];

        if (null !== $containerUid) {
            $defVals['tx_container_parent'] = $containerUid;
        }

        return $this->uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => ['tt_content' => [$pid => 'new']],
                'defVals' => ['tt_content' => $defVals],
                'returnUrl' => $returnUrl,
            ],
        )->__toString();
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
        try {
            $columns = $this->connectionPool
                ->getConnectionForTable('tt_content')
                ->createSchemaManager()
                ->listTableColumns('tt_content');

            return isset($columns['tx_container_parent']);
        } catch (Throwable) {
            return false;
        }
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
