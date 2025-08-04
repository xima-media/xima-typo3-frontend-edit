<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "xima_typo3_frontend_edit".
 *
 * Copyright (C) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Xima\XimaTypo3FrontendEdit\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\VersionCompatibilityService;

final class ContentElementRepository
{
    private const MAX_CACHE_SIZE = 100;
    private const CACHE_CLEANUP_THRESHOLD = 80;

    private \ArrayObject $rootlineCache;
    private \ArrayObject $configCache;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly VersionCompatibilityService $versionCompatibilityService
    ) {
        $this->rootlineCache = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
        $this->configCache = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
    * @return array<int, array<string, mixed>> Array of content element records
    * @throws Exception
    */
    public function fetchContentElements(
        int $pid,
        int $languageUid,
        bool $includeMultilingualContent = true
    ): array {
        try {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

            $query = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'hidden',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                    )
                );

            if ($includeMultilingualContent) {
                $query->andWhere(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter(-1, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                        )
                    )
                );
            } else {
                $query->andWhere(
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                    )
                );
            }

            return $query->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $exception) {
            throw new Exception(
                'Failed to fetch content elements for page ' . $pid . ': ' . $exception->getMessage(),
                1640000010,
                $exception
            );
        }
    }

    /**
    * @throws \Doctrine\DBAL\Exception
    */
    public function getTranslatedRecord(
        string $table,
        int $parentUid,
        int $languageUid
    ): array|false {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
    }

    public function getContentElementConfig(string $cType, string $listType): array|false
    {
        $cacheKey = $cType . ':' . $listType;

        if ($this->configCache->offsetExists($cacheKey)) {
            return $this->configCache->offsetGet($cacheKey);
        }

        if (!isset($GLOBALS['TCA']['tt_content']['columns'])) {
            return false;
        }

        $tca = $cType === 'list'
            ? $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] ?? []
            : $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [];

        $valueKey = $this->versionCompatibilityService->getContentElementConfigValueKey();

        foreach ($tca as $item) {
            if (($cType === 'list' && $item[$valueKey] === $listType) ||
                $item[$valueKey] === $cType) {
                $config = $this->mapContentElementConfig($item);
                $this->manageCacheSize($this->configCache);
                $this->configCache->offsetSet($cacheKey, $config);
                return $config;
            }
        }

        $this->manageCacheSize($this->configCache);
        $this->configCache->offsetSet($cacheKey, false);
        return false;
    }

    public function isSubpageOf(int $subPageId, int $parentPageId): bool
    {
        $cacheKey = $subPageId . ':' . $parentPageId;

        if ($this->rootlineCache->offsetExists($cacheKey)) {
            return $this->rootlineCache->offsetGet($cacheKey);
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

    public function isSubpageOfAny(int $subPageId, array $parentPageIds): bool
    {
        foreach ($parentPageIds as $parentPageId) {
            if ($this->isSubpageOf($subPageId, (int)$parentPageId)) {
                return true;
            }
        }

        return false;
    }

    public function clearCache(): void
    {
        $this->rootlineCache->exchangeArray([]);
        $this->configCache->exchangeArray([]);
    }

    private function manageCacheSize(\ArrayObject $cache): void
    {
        if ($cache->count() >= self::CACHE_CLEANUP_THRESHOLD) {
            $entries = $cache->getArrayCopy();
            $keysToRemove = array_slice(array_keys($entries), 0, $cache->count() - self::MAX_CACHE_SIZE + 20);

            foreach ($keysToRemove as $key) {
                $cache->offsetUnset($key);
            }
        }
    }

    private function mapContentElementConfig(array $config): array
    {
        if ($this->versionCompatibilityService->isVersionBelow12()) {
            for ($i = 0; $i <= 3; $i++) {
                if (!isset($config[$i])) {
                    $config[$i] = '';
                }
            }
            return [
                'label' => $config[0],
                'value' => $config[1],
                'icon' => $config[2],
                'group' => $config[3],
            ];
        }

        return $config;
    }
}
