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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Authentication;

use KonradMichalik\Ttt\Attribute\{WithGlobal, WithInstance};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures\{FakeBackendUser, FakeTypo3Version};

/**
 * BackendUserServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(BackendUserService::class)]
final class BackendUserServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function getBackendUserReturnsNullWhenNotSet(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new BackendUserService();

        self::assertNull($service->getBackendUser());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function getBackendUserReturnsBackendUserFromGlobals(): void
    {
        $service = new BackendUserService();

        self::assertSame($GLOBALS['BE_USER'], $service->getBackendUser());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function getBackendUserCachesResult(): void
    {
        $service = new BackendUserService();

        $first = $service->getBackendUser();
        $second = $service->getBackendUser();

        self::assertSame($first, $second);
    }

    #[Test]
    public function hasPageAccessReturnsFalseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new BackendUserService();

        self::assertFalse($service->hasPageAccess(1));
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(user: null))]
    public function hasPageAccessReturnsFalseWhenUserDataIsNull(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->hasPageAccess(1));
    }

    #[Test]
    public function hasRecordEditAccessReturnsFalseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new BackendUserService();

        self::assertFalse($service->hasRecordEditAccess('tt_content', ['uid' => 1]));
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(user: null))]
    public function hasRecordEditAccessReturnsFalseWhenUserDataIsNull(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->hasRecordEditAccess('tt_content', ['uid' => 1]));
    }

    #[Test]
    public function isFrontendEditDisabledReturnsTrueWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new BackendUserService();

        self::assertTrue($service->isFrontendEditDisabled());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(user: null))]
    public function isFrontendEditDisabledReturnsTrueWhenUserDataIsNull(): void
    {
        $service = new BackendUserService();

        self::assertTrue($service->isFrontendEditDisabled());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function isFrontendEditDisabledReturnsFalseByDefault(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->isFrontendEditDisabled());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(uc: [Configuration::UC_KEY_DISABLED => true]))]
    public function isFrontendEditDisabledReturnsTrueWhenUcKeyIsSet(): void
    {
        $service = new BackendUserService();

        self::assertTrue($service->isFrontendEditDisabled());
    }

    #[Test]
    public function isLoggedInReturnsFalseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        self::assertFalse((new BackendUserService())->isLoggedIn());
    }

    /**
     * @param array<string, mixed>|null $user
     * @param array<string, mixed>      $uc
     * @param array<string, mixed>      $tsConfig
     */
    #[Test]
    #[DataProvider('loggedInDataProvider')]
    public function isLoggedInDependsOnTheUserDataOnly(?array $user, array $uc, array $tsConfig, bool $expected): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = $user;
        $backendUser->uc = $uc;
        $backendUser->method('getTSConfig')->willReturn($tsConfig);
        $GLOBALS['BE_USER'] = $backendUser;

        self::assertSame($expected, (new BackendUserService())->isLoggedIn());
    }

    /**
     * @return iterable<string, array{array<string, mixed>|null, array<string, mixed>, array<string, mixed>, bool}>
     */
    public static function loggedInDataProvider(): iterable
    {
        yield 'user data is null' => [null, [], [], false];
        yield 'user data present' => [['uid' => 1], [], [], true];
        yield 'personal switch off' => [['uid' => 1], [Configuration::UC_KEY_DISABLED => true], [], true];
        yield 'disabled by UserTSconfig' => [['uid' => 1], [], [Configuration::USER_TSCONFIG_KEY => [Configuration::USER_TSCONFIG_DISABLED => '1']], true];
    }

    #[Test]
    public function isFrontendEditAllowedReturnsFalseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new BackendUserService();

        self::assertFalse($service->isFrontendEditAllowed());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(user: null))]
    public function isFrontendEditAllowedReturnsFalseWhenUserDataIsNull(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->isFrontendEditAllowed());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function isFrontendEditAllowedReturnsTrueByDefault(): void
    {
        $service = new BackendUserService();

        self::assertTrue($service->isFrontendEditAllowed());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(tsConfig: [Configuration::USER_TSCONFIG_KEY => [Configuration::USER_TSCONFIG_DISABLED => '1']]))]
    public function isFrontendEditAllowedReturnsFalseWhenDisabledViaTsConfig(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->isFrontendEditAllowed());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(tsConfig: [Configuration::USER_TSCONFIG_KEY => [Configuration::USER_TSCONFIG_DISABLED => '0']]))]
    public function isFrontendEditAllowedReturnsTrueWhenExplicitlyEnabled(): void
    {
        $service = new BackendUserService();

        self::assertTrue($service->isFrontendEditAllowed());
    }

    #[Test]
    public function hasPageEditAccessReturnsFalseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1]));
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(user: null))]
    public function hasPageEditAccessReturnsFalseWhenUserDataIsNull(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1]));
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(admin: true))]
    public function hasPageEditAccessReturnsTrueForAdminRegardlessOfPermissionBits(): void
    {
        $service = new BackendUserService();

        self::assertTrue($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    public function hasPageEditAccessReturnsFalseWhenTablesModifyDeniesPages(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('check')->with('tables_modify', 'pages')->willReturn(false);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    public function hasPageEditAccessReturnsFalseWhenPageEditPermissionBitIsMissing(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('check')->with('tables_modify', 'pages')->willReturn(true);
        $backendUser->method('calcPerms')->willReturn(Permission::PAGE_SHOW);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(permissions: Permission::PAGE_EDIT, checkResult: ['tables_modify:pages' => true, 'pagetypes_select:1' => false], recordEditAccess: true))]
    public function hasPageEditAccessReturnsFalseWhenPagetypesSelectDeniesDoktype(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    #[WithInstance(Typo3Version::class, new FakeTypo3Version(13))]
    #[WithGlobal('BE_USER', new FakeBackendUser(permissions: Permission::PAGE_EDIT, checkResult: ['tables_modify:pages' => true, 'pagetypes_select:1' => true]))]
    public function hasPageEditAccessReturnsFalseWhenRecordEditAccessDenies(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    #[WithInstance(Typo3Version::class, new FakeTypo3Version(13))]
    #[WithGlobal('BE_USER', new FakeBackendUser(permissions: Permission::PAGE_EDIT, recordEditAccess: true, checkResult: ['tables_modify:pages' => true, 'pagetypes_select:1' => true]))]
    public function hasPageEditAccessReturnsTrueWhenAllChecksPass(): void
    {
        $service = new BackendUserService();

        self::assertTrue($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    #[WithInstance(Typo3Version::class, new FakeTypo3Version(13))]
    #[WithGlobal('BE_USER', new FakeBackendUser(permissions: Permission::PAGE_EDIT, recordEditAccess: true, checkResult: ['tables_modify:pages' => true, 'pagetypes_select:1' => true]))]
    public function hasPageEditAccessDefaultsDoktypeToStandardWhenMissing(): void
    {
        $service = new BackendUserService();

        self::assertTrue($service->hasPageEditAccess(['uid' => 1]));
    }

    #[Test]
    public function hasFieldAccessReturnsFalseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new BackendUserService();

        self::assertFalse($service->hasFieldAccess('tt_content', 'hidden'));
    }

    #[Test]
    public function hasFieldAccessDelegatesToBackendUserNonExcludeFieldsCheck(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('check')->with('non_exclude_fields', 'tt_content:hidden')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertTrue($service->hasFieldAccess('tt_content', 'hidden'));
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function hasFieldAccessReturnsFalseWhenCheckDenies(): void
    {
        $service = new BackendUserService();

        self::assertFalse($service->hasFieldAccess('tt_content', 'hidden'));
    }

    /*
     * hasRecordEditAccess() (used by hasPageEditAccess()) branches on the installed
     * TYPO3 version: recordEditAccessInternals() on 13, checkRecordEditAccess() on
     * 14.2+ (see BackendUserUtility). The latter returns a final AccessCheckResult,
     * which PHPUnit cannot double for an unstubbed call - so tests that need a
     * deterministic hasRecordEditAccess() result force the v13 branch, exactly like
     * BackendUserUtilityTest already does for the same reason.
     */
}
