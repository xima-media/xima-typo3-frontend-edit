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
}
