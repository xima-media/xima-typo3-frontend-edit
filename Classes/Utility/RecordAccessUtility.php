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

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use WeakMap;
use Xima\XimaTypo3FrontendEdit\Utility\Compatibility\BackendUserUtility;

/**
 * RecordAccessUtility.
 *
 * recordEditAccessInternals() and checkRecordEditAccess() only look at language
 * access, authMode fields and editlock. The table and page permissions have to be
 * checked here before a record, which comes from client-supplied uids, is exposed.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class RecordAccessUtility
{
    /**
     * @var WeakMap<BackendUserAuthentication, array<int, bool>>|null
     */
    private static ?WeakMap $contentEditByPage = null;

    /**
     * @param array<string, mixed> $record
     */
    public static function canEdit(BackendUserAuthentication $backendUser, string $table, array $record): bool
    {
        if (!self::hasTableAndPageAccess($backendUser, $table, $record)) {
            return false;
        }

        return BackendUserUtility::hasRecordEditAccess($backendUser, $table, $record);
    }

    /**
     * @param array<string, mixed> $record
     */
    private static function hasTableAndPageAccess(BackendUserAuthentication $backendUser, string $table, array $record): bool
    {
        if ($backendUser->isAdmin()) {
            return true;
        }

        if ((bool) ($GLOBALS['TCA'][$table]['ctrl']['adminOnly'] ?? false) || !$backendUser->check('tables_modify', $table)) {
            return false;
        }

        if ('pages' === $table) {
            return (new Permission($backendUser->calcPerms($record)))->editPagePermissionIsGranted();
        }

        $pageId = (int) ($record['pid'] ?? 0);
        if (0 === $pageId) {
            return (bool) ($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreRootLevelRestriction'] ?? false);
        }

        return self::canEditContentOnPage($backendUser, $pageId);
    }

    private static function canEditContentOnPage(BackendUserAuthentication $backendUser, int $pageId): bool
    {
        self::$contentEditByPage ??= new WeakMap();
        $cache = self::$contentEditByPage[$backendUser] ?? [];

        if (!isset($cache[$pageId])) {
            $page = BackendUtility::getRecord('pages', $pageId);
            $cache[$pageId] = null !== $page && (new Permission($backendUser->calcPerms($page)))->editContentPermissionIsGranted();
            self::$contentEditByPage[$backendUser] = $cache;
        }

        return $cache[$pageId];
    }
}
