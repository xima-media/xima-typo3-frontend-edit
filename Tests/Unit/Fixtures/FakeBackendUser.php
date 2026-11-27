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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\{AccessCheckResult, BackendUserAuthentication};
use TYPO3\CMS\Core\Session\UserSessionManager;

use function array_key_exists;
use function is_bool;

/**
 * FakeBackendUser.
 *
 * Constructor-only stand-in for BackendUserAuthentication, for use with
 * #[WithGlobal('BE_USER', ...)]. The defaults match an unconfigured mock: not an admin,
 * no permissions, empty TSconfig. It only returns the configured values and does not
 * verify calls.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class FakeBackendUser extends BackendUserAuthentication
{
    /**
     * @param array<string, mixed>|null $user
     * @param array<string, mixed>      $uc
     * @param array<string, mixed>      $tsConfig
     * @param array<string, bool>|bool  $checkResult one result for check(), or results by "type:value", false for others
     * @param array<string, mixed>      $sessionData session values by key, set values are kept in memory
     */
    public function __construct(
        ?array $user = ['uid' => 1],
        array $uc = [],
        private readonly bool $admin = false,
        private readonly array $tsConfig = [],
        private readonly array|bool $checkResult = false,
        private readonly bool $recordEditAccess = false,
        private readonly int $permissions = 0,
        private array $sessionData = [],
        private readonly mixed $defaultSessionData = null,
    ) {
        $this->user = $user;
        $this->uc = $uc;
    }

    /**
     * No-op like on a mock: Bootstrap calls it on the user when the user data is missing.
     */
    public function backendCheckLogin(?ServerRequestInterface $request = null): void {}

    public function initializeUserSessionManager(?UserSessionManager $userSessionManager = null): void {}

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function getTSConfig(): array
    {
        return $this->tsConfig;
    }

    public function check($type, $value): bool
    {
        if (is_bool($this->checkResult)) {
            return $this->checkResult;
        }

        return $this->checkResult[$type.':'.$value] ?? false;
    }

    public function calcPerms($row, bool $useDeleteClause = true): int
    {
        return $this->permissions;
    }

    public function recordEditAccessInternals(string $table, $row, $newRecord = false, $_ = null, $checkFullLanguageAccess = false): bool
    {
        return $this->recordEditAccess;
    }

    /**
     * Only called on TYPO3 14, where BackendUserUtility uses it instead of recordEditAccessInternals().
     */
    public function checkRecordEditAccess(string $table, $row, bool $newRecord = false, bool $checkFullLanguageAccess = false): AccessCheckResult
    {
        return new AccessCheckResult($this->recordEditAccess);
    }

    public function getSessionData($key)
    {
        return array_key_exists($key, $this->sessionData) ? $this->sessionData[$key] : $this->defaultSessionData;
    }

    public function setAndSaveSessionData($key, $data): void
    {
        $this->sessionData[$key] = $data;
    }
}
