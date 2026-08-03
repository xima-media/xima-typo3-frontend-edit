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

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;

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
    public function getBackendUserReturnsBackendUserFromGlobals(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertSame($backendUser, $service->getBackendUser());
    }

    #[Test]
    public function getBackendUserCachesResult(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $GLOBALS['BE_USER'] = $backendUser;

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
    public function hasPageAccessReturnsFalseWhenUserDataIsNull(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = null;
        $GLOBALS['BE_USER'] = $backendUser;

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
    public function hasRecordEditAccessReturnsFalseWhenUserDataIsNull(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = null;
        $GLOBALS['BE_USER'] = $backendUser;

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
    public function isFrontendEditDisabledReturnsTrueWhenUserDataIsNull(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = null;
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertTrue($service->isFrontendEditDisabled());
    }

    #[Test]
    public function isFrontendEditDisabledReturnsFalseByDefault(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = [];
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->isFrontendEditDisabled());
    }

    #[Test]
    public function isFrontendEditDisabledReturnsTrueWhenUcKeyIsSet(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = [Configuration::UC_KEY_DISABLED => true];
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertTrue($service->isFrontendEditDisabled());
    }

    #[Test]
    public function isFrontendEditAllowedReturnsFalseWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $service = new BackendUserService();

        self::assertFalse($service->isFrontendEditAllowed());
    }

    #[Test]
    public function isFrontendEditAllowedReturnsFalseWhenUserDataIsNull(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = null;
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->isFrontendEditAllowed());
    }

    #[Test]
    public function isFrontendEditAllowedReturnsTrueByDefault(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getTSConfig')->willReturn([]);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertTrue($service->isFrontendEditAllowed());
    }

    #[Test]
    public function isFrontendEditAllowedReturnsFalseWhenDisabledViaTsConfig(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getTSConfig')->willReturn([
            Configuration::USER_TSCONFIG_KEY => [
                Configuration::USER_TSCONFIG_DISABLED => '1',
            ],
        ]);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->isFrontendEditAllowed());
    }

    #[Test]
    public function isFrontendEditAllowedReturnsTrueWhenExplicitlyEnabled(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('getTSConfig')->willReturn([
            Configuration::USER_TSCONFIG_KEY => [
                Configuration::USER_TSCONFIG_DISABLED => '0',
            ],
        ]);
        $GLOBALS['BE_USER'] = $backendUser;

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
    public function hasPageEditAccessReturnsFalseWhenUserDataIsNull(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = null;
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1]));
    }

    #[Test]
    public function hasPageEditAccessReturnsTrueForAdminRegardlessOfPermissionBits(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('isAdmin')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUser;

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
    public function hasPageEditAccessReturnsFalseWhenPagetypesSelectDeniesDoktype(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('calcPerms')->willReturn(Permission::PAGE_EDIT);
        $backendUser->method('check')->willReturnMap([
            ['tables_modify', 'pages', true],
            ['pagetypes_select', '1', false],
        ]);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    public function hasPageEditAccessReturnsFalseWhenRecordEditAccessDenies(): void
    {
        $this->registerVersion13();

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('calcPerms')->willReturn(Permission::PAGE_EDIT);
        $backendUser->method('check')->willReturnMap([
            ['tables_modify', 'pages', true],
            ['pagetypes_select', '1', true],
        ]);
        // e.g. editlock or a restricted language on the page record itself -
        // tables_modify/PAGE_EDIT/pagetypes_select all pass, but the record
        // itself is still locked, which only hasRecordEditAccess() catches.
        $backendUser->method('recordEditAccessInternals')->willReturn(false);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    public function hasPageEditAccessReturnsTrueWhenAllChecksPass(): void
    {
        $this->registerVersion13();

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('calcPerms')->willReturn(Permission::PAGE_EDIT);
        $backendUser->method('check')->willReturnMap([
            ['tables_modify', 'pages', true],
            ['pagetypes_select', '1', true],
        ]);
        $backendUser->method('recordEditAccessInternals')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertTrue($service->hasPageEditAccess(['uid' => 1, 'doktype' => 1]));
    }

    #[Test]
    public function hasPageEditAccessDefaultsDoktypeToStandardWhenMissing(): void
    {
        $this->registerVersion13();

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->method('calcPerms')->willReturn(Permission::PAGE_EDIT);
        $backendUser->method('check')->willReturnMap([
            ['tables_modify', 'pages', true],
            ['pagetypes_select', '1', true],
        ]);
        $backendUser->method('recordEditAccessInternals')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUser;

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
    public function hasFieldAccessReturnsFalseWhenCheckDenies(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->method('check')->willReturn(false);
        $GLOBALS['BE_USER'] = $backendUser;

        $service = new BackendUserService();

        self::assertFalse($service->hasFieldAccess('tt_content', 'hidden'));
    }

    /**
     * hasRecordEditAccess() (used by hasPageEditAccess()) branches on the installed
     * TYPO3 version: recordEditAccessInternals() on 13, checkRecordEditAccess() on
     * 14.2+ (see BackendUserUtility). The latter returns a final AccessCheckResult,
     * which PHPUnit cannot double for an unstubbed call - so tests that need a
     * deterministic hasRecordEditAccess() result force the v13 branch, exactly like
     * BackendUserUtilityTest already does for the same reason.
     */
    private function registerVersion13(): void
    {
        $versionMock = $this->createMock(Typo3Version::class);
        $versionMock->method('getMajorVersion')->willReturn(13);
        GeneralUtility::addInstance(Typo3Version::class, $versionMock);
    }
}
