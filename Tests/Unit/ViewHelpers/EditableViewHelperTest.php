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

use KonradMichalik\Ttt\Attribute\WithGlobal;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use stdClass;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures\FakeBackendUser;
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

    /**
     * All backend users share one page cache entry, so the output must not depend on the
     * personal switch or the UserTSconfig. ToolRendererMiddleware and the JavaScript apply them.
     *
     * @param array<string, mixed> $uc
     * @param array<string, mixed> $tsConfig
     */
    #[Test]
    #[DataProvider('personalSettingsDataProvider')]
    public function renderIgnoresPersonalSettingsOfTheBackendUser(array $uc, array $tsConfig): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        $backendUser->uc = $uc;
        $backendUser->method('getTSConfig')->willReturn($tsConfig);
        $GLOBALS['BE_USER'] = $backendUser;

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tt_content']);

        self::assertSame(' data-frontend-edit="tt_content:42"', $viewHelper->render());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function personalSettingsDataProvider(): iterable
    {
        yield 'personal switch off' => [[Configuration::UC_KEY_DISABLED => true], []];
        yield 'disabled by UserTSconfig' => [[], [Configuration::USER_TSCONFIG_KEY => [Configuration::USER_TSCONFIG_DISABLED => '1']]];
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderReturnsEmptyStringWhenNeitherRecordNorUidGiven(): void
    {
        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => null, 'table' => 'tt_content']);

        self::assertSame('', $viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderReturnsEmptyStringForNonPositiveUid(): void
    {
        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 0, 'table' => 'tt_content']);

        self::assertSame('', $viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderReturnsDataAttributeFromUidArgument(): void
    {
        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tt_content']);

        self::assertSame(' data-frontend-edit="tt_content:42"', $viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderReturnsDataAttributeFromRecordArgument(): void
    {
        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => ['uid' => 99, 'CType' => 'text'], 'uid' => null, 'table' => 'tt_content']);

        self::assertSame(' data-frontend-edit="tt_content:99"', $viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderReturnsDataAttributeFromRecordObject(): void
    {
        // Stands in for Extbase models and core Record objects, both expose getUid().
        $record = new class {
            public function getUid(): int
            {
                return 7;
            }
        };

        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => $record, 'uid' => null, 'table' => 'tx_news_domain_model_news']);

        self::assertSame(' data-frontend-edit="tx_news_domain_model_news:7"', $viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderReturnsEmptyStringForRecordObjectWithoutUid(): void
    {
        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => new stdClass(), 'uid' => null, 'table' => 'tt_content']);

        self::assertSame('', $viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderPrefersUidArgumentOverRecordArgument(): void
    {
        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => ['uid' => 99], 'uid' => 42, 'table' => 'tt_content']);

        self::assertSame(' data-frontend-edit="tt_content:42"', $viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderReturnsDataAttributeWithCustomTable(): void
    {
        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tx_news_domain_model_news']);

        self::assertSame(' data-frontend-edit="tx_news_domain_model_news:42"', $viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser())]
    public function renderReturnsEmptyStringForInvalidTableFormat(): void
    {
        $viewHelper = new EditableViewHelper(new BackendUserService());
        $viewHelper->initializeArguments();
        $viewHelper->setArguments(['record' => null, 'uid' => 42, 'table' => 'tt_content"><script>']);

        self::assertSame('', $viewHelper->render());
    }
}
