<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class ContentUtility
{
    public static function fetchContentElements(int $pid, int $languageUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        return $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)),
            )
            ->executeQuery()->fetchAllAssociative();
    }

    public static function getContentElementConfig(string $cType, string $listType): array|bool
    {
        $tca = $cType === 'list' ? $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] : $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];

        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '12.0.0', '<')) {
            $valueKey = 1;
        } else {
            $valueKey = 'value';
        }
        foreach ($tca as $item) {
            if (($cType === 'list' && $item[$valueKey] === $listType) || $item[$valueKey] === $cType) {
                return self::mapContentElementConfig($item);
            }
        }

        return false;
    }

    public static function isSubpageOf(int $subPageId, int $parentPageId): bool
    {
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $subPageId)->get();
        foreach ($rootLine as $page) {
            if ((int)$page['uid'] === (int)$parentPageId) {
                return true;
            }
        }

        return false;
    }

    public static function shortenString(string $string, int $maxLength = 30): string
    {
        return strlen($string) > $maxLength ? substr($string, 0, $maxLength) . '…' : $string;
    }

    public static function mapContentElementConfig(array $config): array
    {
        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '12.0.0', '>=')) {
            return $config;
        }
        return [
            'label' => $config[0],
            'value' => $config[1],
            'icon' => $config[2],
            'group' => $config[3],
        ];
    }
}
