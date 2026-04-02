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

namespace Xima\XimaTypo3FrontendEdit\Utility\Compatibility;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * BackendUserUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class BackendUserUtility
{
    /**
     * Check if a backend user has edit access to a record.
     *
     * Uses checkRecordEditAccess() on TYPO3 v14.2+ (where recordEditAccessInternals is deprecated)
     * and falls back to recordEditAccessInternals() on older versions.
     *
     * @param array<string, mixed> $record
     *
     * @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.2/Deprecation-108568-BackendUserAuthenticationRecordEditAccessInternalsAndErrorMsg.html
     */
    public static function hasRecordEditAccess(BackendUserAuthentication $backendUser, string $table, array $record): bool
    {
        if (method_exists($backendUser, 'checkRecordEditAccess')) { // @phpstan-ignore function.alreadyNarrowedType (v13 compat)
            return $backendUser->checkRecordEditAccess($table, $record)->isAllowed;
        }

        return $backendUser->recordEditAccessInternals($table, $record); // @phpstan-ignore method.deprecated (v13 compat)
    }
}
