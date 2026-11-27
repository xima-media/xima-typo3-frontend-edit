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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Utility;

use KonradMichalik\Ttt\Attribute\WithTca;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use Xima\XimaTypo3FrontendEdit\Utility\RecordAccessUtility;

/**
 * RecordAccessUtilityTest.
 *
 * The positive path on a page needs the database and is covered by
 * Tests/Functional/Controller/AjaxControllerRecordAccessTest.php.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(RecordAccessUtility::class)]
final class RecordAccessUtilityTest extends TestCase
{
    #[Test]
    #[WithTca('be_users', ['ctrl' => ['adminOnly' => true]])]
    public function adminOnlyTablesAreDeniedForNonAdmins(): void
    {
        $backendUser = $this->createBackendUser(tablesModify: true);

        self::assertFalse(RecordAccessUtility::canEdit($backendUser, 'be_users', ['uid' => 1, 'pid' => 5]));
    }

    #[Test]
    #[WithTca('tt_content', ['ctrl' => []])]
    public function tablesWithoutTablesModifyAreDenied(): void
    {
        $backendUser = $this->createBackendUser(tablesModify: false);

        self::assertFalse(RecordAccessUtility::canEdit($backendUser, 'tt_content', ['uid' => 1, 'pid' => 5]));
    }

    #[Test]
    #[WithTca('sys_file_storage', ['ctrl' => []])]
    public function rootLevelRecordsAreDeniedForNonAdmins(): void
    {
        $backendUser = $this->createBackendUser(tablesModify: true);

        self::assertFalse(RecordAccessUtility::canEdit($backendUser, 'sys_file_storage', ['uid' => 1, 'pid' => 0]));
    }

    #[Test]
    #[WithTca('pages', ['ctrl' => []])]
    public function pagesWithoutPageEditPermissionAreDenied(): void
    {
        $backendUser = $this->createBackendUser(tablesModify: true, pagePermissions: Permission::CONTENT_EDIT);

        self::assertFalse(RecordAccessUtility::canEdit($backendUser, 'pages', ['uid' => 5, 'pid' => 1]));
    }

    private function createBackendUser(bool $tablesModify, int $pagePermissions = Permission::NOTHING): BackendUserAuthentication
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('check')->willReturn($tablesModify);
        $backendUser->method('calcPerms')->willReturn($pagePermissions);
        $backendUser->method('recordEditAccessInternals')->willReturn(true);

        return $backendUser;
    }
}
