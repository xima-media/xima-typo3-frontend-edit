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

namespace Xima\XimaTypo3FrontendEdit\Repository;

use ArrayObject;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\{GeneralUtility, RootlineUtility};

use function array_slice;
use function in_array;

/**
 * ContentElementRepository.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class ContentElementRepository
{
    private const MAX_CACHE_SIZE = 100;
    private const CACHE_CLEANUP_THRESHOLD = 80;

    /**
     * @var ArrayObject<string, list<int>>
     */
    private ArrayObject $rootlineCache;

    /**
     * @var ArrayObject<string, array<string, mixed>|false>
     */
    private ArrayObject $configCache;

    /**
     * @var ArrayObject<string, array<string, array<string, mixed>>>
     */
    private ArrayObject $tcaItemMapCache;

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {
        $this->rootlineCache = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $this->configCache = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $this->tcaItemMapCache = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @return array<int, array<string, mixed>> Array of content element records
     *
     * @throws Exception
     */
    public function fetchContentElements(
        int $pid,
        int $languageUid,
    ): array {
        try {
            $queryBuilder = $this->buildContentQuery($languageUid, true);

            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT),
                ),
            );

            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $exception) {
            throw new Exception('Failed to fetch content elements for page '.$pid.': '.$exception->getMessage(), 1640000010, $exception);
        }
    }

    /**
     * Fetch content elements by UIDs regardless of PID.
     *
     * This method is used in onepager scenarios where content from multiple pages
     * is rendered on a single page. Permission checks are performed per-element
     * in the ContentElementFilter layer.
     *
     * In connected/overlay language mode the given UIDs are the default-language
     * (L0) uids taken from the DOM anchors; translations are resolved via
     * l18n_parent and the matching variant is selected in resolveLanguageVariants()
     * (translation wins over L0). Free mode and the default language are unaffected.
     *
     * @param array<int> $uids Array of content element UIDs
     *
     * @return array<int, array<string, mixed>> Array of content element records
     *
     * @throws Exception
     *
     * @see ContentElementFilter::shouldIncludeElement() for permission validation
     */
    public function fetchContentElementsByUids(
        array $uids,
        int $languageUid,
        bool $includeMultilingualContent = true,
    ): array {
        if ([] === $uids) {
            return [];
        }

        try {
            if (!$includeMultilingualContent) {
                $queryBuilder = $this->buildContentQuery($languageUid, false);
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY),
                    ),
                );

                return $queryBuilder->executeQuery()->fetchAllAssociative();
            }

            $queryBuilder = $this->buildContentQueryByUids($uids, $languageUid);
            $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

            return $this->resolveLanguageVariants($rows);
        } catch (\Doctrine\DBAL\Exception $exception) {
            throw new Exception('Failed to fetch content elements by UIDs: '.$exception->getMessage(), 1640000011, $exception);
        }
    }

    /**
     * @return array<string, mixed>|false
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTranslatedRecord(
        string $table,
        int $parentUid,
        int $languageUid,
    ): array|false {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);

        $parentField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? 'l10n_parent';

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * @return array<string, mixed>|false
     */
    public function getContentElementConfig(string $cType, string $listType): array|false
    {
        $cacheKey = $cType.':'.$listType;

        if ($this->configCache->offsetExists($cacheKey)) {
            /** @var array<string, mixed>|false|null $cached */
            $cached = $this->configCache->offsetGet($cacheKey);

            return $cached ?? false;
        }

        if (!isset($GLOBALS['TCA']['tt_content']['columns'])) {
            return false;
        }

        $field = 'list' === $cType ? 'list_type' : 'CType';
        $lookupValue = 'list' === $cType ? $listType : $cType;
        $config = $this->getTcaItemsMap($field)[$lookupValue] ?? false;

        $this->manageCacheSize($this->configCache);
        $this->configCache->offsetSet($cacheKey, $config);

        return $config;
    }

    public function getPageDoktype(int $pid): ?int
    {
        try {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');

            $result = $queryBuilder
                ->select('doktype')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT),
                    ),
                )
                ->executeQuery()
                ->fetchAssociative();

            return false !== $result ? (int) $result['doktype'] : null;
        } catch (\Doctrine\DBAL\Exception) {
            return null;
        }
    }

    public function isSubpageOf(int $subPageId, int $parentPageId): bool
    {
        return in_array($parentPageId, $this->getRootlinePageIds($subPageId), true);
    }

    /**
     * @param array<int, mixed> $parentPageIds
     */
    public function isSubpageOfAny(int $subPageId, array $parentPageIds): bool
    {
        if ([] === $parentPageIds) {
            return false;
        }

        $rootlineIds = $this->getRootlinePageIds($subPageId);

        foreach ($parentPageIds as $parentPageId) {
            if (in_array((int) $parentPageId, $rootlineIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the rootline page uids of a page once and cache them, so multiple
     * ancestor checks for the same page reuse a single RootlineUtility lookup
     * instead of rebuilding the rootline per parent candidate.
     *
     * @return list<int>
     */
    private function getRootlinePageIds(int $subPageId): array
    {
        $cacheKey = (string) $subPageId;

        if ($this->rootlineCache->offsetExists($cacheKey)) {
            /** @var list<int>|null $cached */
            $cached = $this->rootlineCache->offsetGet($cacheKey);

            return $cached ?? [];
        }

        $pageIds = [];

        try {
            foreach (GeneralUtility::makeInstance(RootlineUtility::class, $subPageId)->get() as $page) {
                $pageIds[] = (int) $page['uid'];
            }
        } catch (\Exception) {
            // Page not found or other error
        }

        $this->manageCacheSize($this->rootlineCache);
        $this->rootlineCache->offsetSet($cacheKey, $pageIds);

        return $pageIds;
    }

    /**
     * Build base QueryBuilder for tt_content with common filters.
     *
     * Creates a QueryBuilder with SELECT * FROM tt_content and applies:
     * - hidden = 0 and deleted = 0 conditions
     * - sys_language_uid conditions based on language and multilingual settings
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function buildContentQuery(
        int $languageUid,
        bool $includeMultilingualContent,
    ): \Doctrine\DBAL\Query\QueryBuilder {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

        $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'hidden',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            );

        if ($includeMultilingualContent) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(-1, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT),
                    ),
                ),
            );
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT),
                ),
            );
        }

        return $queryBuilder;
    }

    /**
     * Build a QueryBuilder for the UID-based fetch path (onepager + language overlay).
     *
     * In connected/overlay language mode the DOM anchors (`id="c{uid}"`) carry the
     * default-language (L0) uids, so the requested $uids are L0 uids. This query
     * therefore also matches the translations of those L0 uids via l18n_parent and
     * keeps L0 records (sys_language_uid = 0) so fallback-rendered elements on
     * translated pages still receive a menu. The actual variant is picked in
     * resolveLanguageVariants().
     *
     * @param array<int> $uids
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function buildContentQueryByUids(
        array $uids,
        int $languageUid,
    ): \Doctrine\DBAL\Query\QueryBuilder {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

        $uidsParameter = $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY);

        $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'hidden',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            );

        if ($languageUid > 0) {
            // Match the requested uids directly, or the translations of those uids
            // (connected mode: translation.l18n_parent points to the L0 uid).
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->in('uid', $uidsParameter),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->in('l18n_parent', $uidsParameter),
                        $queryBuilder->expr()->eq(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT),
                        ),
                    ),
                ),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter([-1, 0, $languageUid], Connection::PARAM_INT_ARRAY),
                ),
            );
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('uid', $uidsParameter),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter([-1, 0], Connection::PARAM_INT_ARRAY),
                ),
            );
        }

        return $queryBuilder;
    }

    /**
     * Collapse language variants that map to the same DOM anchor.
     *
     * The DOM anchor of a record is its l18n_parent (for translations) or its own
     * uid (for L0 and all-language records). When both an L0 record and its
     * translation are returned for the same anchor, the translation wins so the
     * edit URL targets the translation uid (FormEngine cannot switch records via a
     * language parameter).
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveLanguageVariants(array $rows): array
    {
        $byAnchor = [];
        foreach ($rows as $row) {
            $languageId = (int) ($row['sys_language_uid'] ?? 0);
            $parent = (int) ($row['l18n_parent'] ?? 0);
            $anchorUid = ($languageId > 0 && $parent > 0) ? $parent : (int) $row['uid'];

            $existing = $byAnchor[$anchorUid] ?? null;
            if (null === $existing || ($languageId > 0 && (int) ($existing['sys_language_uid'] ?? 0) <= 0)) {
                $byAnchor[$anchorUid] = $row;
            }
        }

        return array_values($byAnchor);
    }

    /**
     * Build a value => item lookup map for a tt_content select field once, so
     * repeated config lookups are O(1) instead of scanning the TCA items list.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getTcaItemsMap(string $field): array
    {
        if ($this->tcaItemMapCache->offsetExists($field)) {
            /** @var array<string, array<string, mixed>>|null $cached */
            $cached = $this->tcaItemMapCache->offsetGet($field);

            return $cached ?? [];
        }

        $map = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns'][$field]['config']['items'] ?? [] as $item) {
            if (isset($item['value'])) {
                $map[$item['value']] = $item;
            }
        }

        $this->tcaItemMapCache->offsetSet($field, $map);

        return $map;
    }

    /**
     * @param ArrayObject<string, mixed> $cache
     */
    private function manageCacheSize(ArrayObject $cache): void
    {
        if ($cache->count() >= self::CACHE_CLEANUP_THRESHOLD) {
            $entries = $cache->getArrayCopy();
            $entriesToRemove = $cache->count() - self::MAX_CACHE_SIZE;
            $keysToRemove = array_slice(array_keys($entries), 0, $entriesToRemove);

            foreach ($keysToRemove as $key) {
                $cache->offsetUnset($key);
            }
        }
    }
}
