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

namespace Xima\XimaTypo3FrontendEdit\Service\Authentication;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Utility\Compatibility\BackendUserUtility;

/**
 * BackendUserService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class BackendUserService
{
    private ?BackendUserAuthentication $backendUser = null;

    public function getBackendUser(): ?BackendUserAuthentication
    {
        if (null !== $this->backendUser) {
            return $this->backendUser;
        }

        $this->backendUser = $GLOBALS['BE_USER'] ?? null;

        if (null === $this->backendUser) {
            return null;
        }

        if (null === $this->backendUser->user) {
            $this->initializeBackendUser();
        }

        return $this->backendUser;
    }

    public function hasPageAccess(int $pageId): bool
    {
        $backendUser = $this->getBackendUser();

        if (null === $backendUser || null === $backendUser->user) {
            return false;
        }

        return (bool) BackendUtility::readPageAccess(
            $pageId,
            $backendUser->getPagePermsClause(Permission::PAGE_SHOW),
        );
    }

    /**
     * @param array<string, mixed> $record
     */
    public function hasRecordEditAccess(string $table, array $record): bool
    {
        $backendUser = $this->getBackendUser();

        if (null === $backendUser || null === $backendUser->user) {
            return false;
        }

        return BackendUserUtility::hasRecordEditAccess($backendUser, $table, $record);
    }

    /**
     * Check if frontend edit is disabled by the user's toggle (UC state).
     *
     * This reflects the user's personal on/off toggle and can be changed
     * via the sticky toolbar. The toolbar remains visible when this is true.
     */
    public function isFrontendEditDisabled(): bool
    {
        $backendUser = $this->getBackendUser();

        if (null === $backendUser || null === $backendUser->user) {
            return true;
        }

        return (bool) ($backendUser->uc[Configuration::UC_KEY_DISABLED] ?? false);
    }

    /**
     * Check if frontend edit is allowed for the current backend user via UserTSconfig.
     *
     * When disabled via UserTSconfig, the entire frontend edit feature is hidden
     * including the sticky toolbar — the user cannot re-enable it themselves.
     *
     * UserTSconfig: tx_ximatypo3frontendedit.disabled = 1
     *
     * Default: allowed (enabled) for all backend users.
     */
    public function isFrontendEditAllowed(): bool
    {
        $backendUser = $this->getBackendUser();

        if (null === $backendUser || null === $backendUser->user) {
            return false;
        }

        $tsConfig = $backendUser->getTSConfig();
        $extConfig = $tsConfig[Configuration::USER_TSCONFIG_KEY] ?? [];

        return !((bool) ($extConfig[Configuration::USER_TSCONFIG_DISABLED] ?? false));
    }

    private function initializeBackendUser(): void
    {
        Bootstrap::initializeBackendAuthentication();
        if (null !== $this->backendUser) {
            $this->backendUser->initializeUserSessionManager();
        }
        $this->backendUser = $GLOBALS['BE_USER'] ?? null;
    }
}
