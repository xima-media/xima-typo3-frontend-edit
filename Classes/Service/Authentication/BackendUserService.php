<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service\Authentication;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

final class BackendUserService
{
    private ?BackendUserAuthentication $backendUser = null;

    public function getBackendUser(): ?BackendUserAuthentication
    {
        if ($this->backendUser !== null) {
            return $this->backendUser;
        }

        $this->backendUser = $GLOBALS['BE_USER'] ?? null;

        if ($this->backendUser === null) {
            return null;
        }

        if ($this->backendUser->user === null) {
            $this->initializeBackendUser();
        }

        return $this->backendUser;
    }

    public function hasPageAccess(int $pageId): bool
    {
        $backendUser = $this->getBackendUser();

        if ($backendUser === null || $backendUser->user === null) {
            return false;
        }

        return (bool)BackendUtility::readPageAccess(
            $pageId,
            $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
        );
    }

    public function hasRecordEditAccess(string $table, array $record): bool
    {
        $backendUser = $this->getBackendUser();

        if ($backendUser === null || $backendUser->user === null) {
            return false;
        }

        return $backendUser->recordEditAccessInternals($table, $record);
    }

    public function isAdmin(): bool
    {
        $backendUser = $this->getBackendUser();
        return $backendUser !== null && $backendUser->isAdmin();
    }

    public function isFrontendEditDisabled(): bool
    {
        $backendUser = $this->getBackendUser();

        if ($backendUser === null || $backendUser->user === null) {
            return true;
        }

        return isset($backendUser->user['tx_ximatypo3frontendedit_disable'])
            && $backendUser->user['tx_ximatypo3frontendedit_disable'];
    }

    private function initializeBackendUser(): void
    {
        Bootstrap::initializeBackendAuthentication();
        if ($this->backendUser !== null) {
            $this->backendUser->initializeUserSessionManager();
        }
        $this->backendUser = $GLOBALS['BE_USER'] ?? null;
    }
}
