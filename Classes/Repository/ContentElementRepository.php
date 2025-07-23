<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\VersionCompatibilityService;

final class ContentElementRepository
{
    private array $rootlineCache = [];
    private array $configCache = [];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly VersionCompatibilityService $versionCompatibilityService
    ) {
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

        if (isset($this->configCache[$cacheKey])) {
            return $this->configCache[$cacheKey];
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
                $this->configCache[$cacheKey] = $config;
                return $config;
            }
        }

        $this->configCache[$cacheKey] = false;
        return false;
    }

    public function isSubpageOf(int $subPageId, int $parentPageId): bool
    {
        $cacheKey = $subPageId . ':' . $parentPageId;

        if (isset($this->rootlineCache[$cacheKey])) {
            return $this->rootlineCache[$cacheKey];
        }

        try {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $subPageId)->get();

            foreach ($rootLine as $page) {
                if ($page['uid'] === $parentPageId) {
                    $this->rootlineCache[$cacheKey] = true;
                    return true;
                }
            }
        } catch (\Exception) {
            // Page not found or other error
        }

        $this->rootlineCache[$cacheKey] = false;
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
        $this->rootlineCache = [];
        $this->configCache = [];
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
