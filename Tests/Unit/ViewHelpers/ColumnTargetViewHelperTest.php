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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\ViewHelpers\ColumnTargetViewHelper;

/**
 * ColumnTargetViewHelperTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ColumnTargetViewHelper::class)]
final class ColumnTargetViewHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function renderReturnsEmptyStringWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $viewHelper = new ColumnTargetViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['colPos' => 0, 'containerUid' => null]);

        self::assertSame('', $viewHelper->render());
    }

    #[Test]
    public function renderReturnsEmptyStringWhenFrontendEditDisabled(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = [Configuration::UC_KEY_DISABLED => true];
        $backendUser->method('getTSConfig')->willReturn([]);
        $GLOBALS['BE_USER'] = $backendUser;

        $viewHelper = new ColumnTargetViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['colPos' => 0, 'containerUid' => null]);

        self::assertSame('', $viewHelper->render());
    }

    #[Test]
    public function renderReturnsHiddenDivWithColPos(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = [];
        $backendUser->method('getTSConfig')->willReturn([]);
        $GLOBALS['BE_USER'] = $backendUser;

        $viewHelper = new ColumnTargetViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['colPos' => 3, 'containerUid' => null]);

        $result = $viewHelper->render();

        self::assertSame('<div data-xfe-colpos="3" hidden></div>', $result);
    }

    #[Test]
    public function renderIncludesContainerUidAttribute(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = [];
        $backendUser->method('getTSConfig')->willReturn([]);
        $GLOBALS['BE_USER'] = $backendUser;

        $viewHelper = new ColumnTargetViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['colPos' => 200, 'containerUid' => 42]);

        $result = $viewHelper->render();

        self::assertSame('<div data-xfe-colpos="200" data-xfe-container="42" hidden></div>', $result);
    }

    #[Test]
    public function renderOmitsContainerAttributeWhenNull(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = [];
        $backendUser->method('getTSConfig')->willReturn([]);
        $GLOBALS['BE_USER'] = $backendUser;

        $viewHelper = new ColumnTargetViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['colPos' => 0, 'containerUid' => null]);

        $result = $viewHelper->render();

        self::assertStringNotContainsString('data-xfe-container', $result);
    }
}
