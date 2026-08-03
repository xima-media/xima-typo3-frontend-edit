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
use Xima\XimaTypo3FrontendEdit\ViewHelpers\EditableViewHelper;

/**
 * EditableViewHelperTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(EditableViewHelper::class)]
final class EditableViewHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function renderReturnsEmptyStringWhenNoBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tt_content']);

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

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tt_content']);

        self::assertSame('', $viewHelper->render());
    }

    #[Test]
    public function renderReturnsEmptyStringWhenNeitherRecordNorUidGiven(): void
    {
        $this->setUpAuthenticatedBackendUser();

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => null, 'table' => 'tt_content']);

        self::assertSame('', $viewHelper->render());
    }

    #[Test]
    public function renderReturnsEmptyStringForNonPositiveUid(): void
    {
        $this->setUpAuthenticatedBackendUser();

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 0, 'table' => 'tt_content']);

        self::assertSame('', $viewHelper->render());
    }

    #[Test]
    public function renderReturnsDataAttributeFromUidArgument(): void
    {
        $this->setUpAuthenticatedBackendUser();

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tt_content']);

        self::assertSame(' data-frontend-edit="tt_content:42"', $viewHelper->render());
    }

    #[Test]
    public function renderReturnsDataAttributeFromRecordArgument(): void
    {
        $this->setUpAuthenticatedBackendUser();

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => ['uid' => 99, 'CType' => 'text'], 'uid' => null, 'table' => 'tt_content']);

        self::assertSame(' data-frontend-edit="tt_content:99"', $viewHelper->render());
    }

    #[Test]
    public function renderPrefersUidArgumentOverRecordArgument(): void
    {
        $this->setUpAuthenticatedBackendUser();

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => ['uid' => 99], 'uid' => 42, 'table' => 'tt_content']);

        self::assertSame(' data-frontend-edit="tt_content:42"', $viewHelper->render());
    }

    #[Test]
    public function renderReturnsDataAttributeWithCustomTable(): void
    {
        $this->setUpAuthenticatedBackendUser();

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tx_news_domain_model_news']);

        self::assertSame(' data-frontend-edit="tx_news_domain_model_news:42"', $viewHelper->render());
    }

    #[Test]
    public function renderReturnsEmptyStringForInvalidTableFormat(): void
    {
        $this->setUpAuthenticatedBackendUser();

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tt_content"><script>']);

        self::assertSame('', $viewHelper->render());
    }

    private function setUpAuthenticatedBackendUser(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = [];
        $backendUser->method('getTSConfig')->willReturn([]);
        $GLOBALS['BE_USER'] = $backendUser;
    }
}
