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

namespace Xima\XimaTypo3FrontendEdit\Service\Authentication;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

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

        return $backendUser->recordEditAccessInternals($table, $record);
    }

    public function isAdmin(): bool
    {
        $backendUser = $this->getBackendUser();

        return null !== $backendUser && $backendUser->isAdmin();
    }

    public function isFrontendEditDisabled(): bool
    {
        $backendUser = $this->getBackendUser();

        if (null === $backendUser || null === $backendUser->user) {
            return true;
        }

        return isset($backendUser->user['tx_ximatypo3frontendedit_disable'])
            && $backendUser->user['tx_ximatypo3frontendedit_disable'];
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
