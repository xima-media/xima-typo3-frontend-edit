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

namespace Xima\XimaTypo3FrontendEdit\Repository;

use ArrayObject;
use Generator;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\{GeneralUtility, RootlineUtility};
use Xima\XimaTypo3FrontendEdit\Service\Configuration\VersionCompatibilityService;

use function array_slice;

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
     * @var ArrayObject<string, bool>
     */
    private ArrayObject $rootlineCache;

    /**
     * @var ArrayObject<string, array<string, mixed>|false>
     */
    private ArrayObject $configCache;

    public function __construct(
        private ConnectionPool $connectionPool,
        private VersionCompatibilityService $versionCompatibilityService,
    ) {
        $this->rootlineCache = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $this->configCache = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @return array<int, array<string, mixed>> Array of content element records
     *
     * @throws Exception
     */
    public function fetchContentElements(
        int $pid,
        int $languageUid,
        bool $includeMultilingualContent = true,
    ): array {
        try {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

            $query = $queryBuilder
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
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT),
                    ),
                );

            if ($includeMultilingualContent) {
                $query->andWhere(
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
                $query->andWhere(
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT),
                    ),
                );
            }

            return $query->executeQuery()->fetchAllAssociative();
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
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

            $query = $queryBuilder
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
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY),
                    ),
                );

            if ($includeMultilingualContent) {
                $query->andWhere(
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
                $query->andWhere(
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT),
                    ),
                );
            }

            return $query->executeQuery()->fetchAllAssociative();
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

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
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
            // @phpstan-ignore-next-line ArrayObject value is guaranteed non-null after offsetExists check
            return $this->configCache[$cacheKey];
        }

        if (!isset($GLOBALS['TCA']['tt_content']['columns'])) {
            return false;
        }

        $valueKey = $this->versionCompatibilityService->getContentElementConfigValueKey();

        // Lazy loading: iterate through TCA items using generator to avoid loading entire array
        foreach ($this->getTcaItemsLazily($cType) as $item) {
            if (('list' === $cType && $item[$valueKey] === $listType)
                || $item[$valueKey] === $cType) {
                $this->manageCacheSize($this->configCache);
                $this->configCache->offsetSet($cacheKey, $item);

                return $item;
            }
        }

        $this->manageCacheSize($this->configCache);
        $this->configCache->offsetSet($cacheKey, false);

        return false;
    }

    public function isSubpageOf(int $subPageId, int $parentPageId): bool
    {
        $cacheKey = $subPageId.':'.$parentPageId;

        if ($this->rootlineCache->offsetExists($cacheKey)) {
            return (bool) $this->rootlineCache->offsetGet($cacheKey);
        }

        try {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $subPageId)->get();

            foreach ($rootLine as $page) {
                if ($page['uid'] === $parentPageId) {
                    $this->manageCacheSize($this->rootlineCache);
                    $this->rootlineCache->offsetSet($cacheKey, true);

                    return true;
                }
            }
        } catch (\Exception) {
            // Page not found or other error
        }

        $this->manageCacheSize($this->rootlineCache);
        $this->rootlineCache->offsetSet($cacheKey, false);

        return false;
    }

    /**
     * @param array<int, mixed> $parentPageIds
     */
    public function isSubpageOfAny(int $subPageId, array $parentPageIds): bool
    {
        foreach ($parentPageIds as $parentPageId) {
            if ($this->isSubpageOf($subPageId, (int) $parentPageId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generator for lazy loading TCA items to reduce memory consumption.
     *
     * @return Generator<int, array<string, mixed>>
     */
    private function getTcaItemsLazily(string $cType): Generator
    {
        $tcaPath = 'list' === $cType
            ? $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] ?? []
            : $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [];

        // Yield items one by one instead of loading entire array into memory
        foreach ($tcaPath as $item) {
            yield $item;
        }
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
