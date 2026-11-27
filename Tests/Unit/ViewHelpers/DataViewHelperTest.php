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
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures\FakeBackendUser;
use Xima\XimaTypo3FrontendEdit\ViewHelpers\DataViewHelper;

/**
 * DataViewHelperTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(DataViewHelper::class)]
final class DataViewHelperTest extends TestCase
{
    private DataViewHelper $viewHelper;

    protected function setUp(): void
    {
        $this->viewHelper = new DataViewHelper();
        $this->viewHelper->initializeArguments();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    #[WithGlobal('BE_USER', null)]
    public function renderReturnsEmptyStringWhenNoBackendUser(): void
    {
        $this->viewHelper->setArguments([
            'label' => 'Test',
            'uid' => 123,
            'table' => 'tt_content',
            'url' => null,
            'icon' => null,
            'class' => '',
        ]);

        self::assertSame('', $this->viewHelper->render());
    }

    /**
     * All backend users share one page cache entry, so the output must not depend on the
     * personal switch. The JavaScript applies it.
     */
    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(uc: [Configuration::UC_KEY_DISABLED => true]))]
    public function renderIgnoresThePersonalSwitchOfTheBackendUser(): void
    {
        $this->viewHelper->setArguments([
            'label' => 'Test',
            'uid' => 123,
            'table' => 'tt_content',
            'url' => null,
            'icon' => null,
            'class' => '',
        ]);

        self::assertStringStartsWith('<input type="hidden"', $this->viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(uc: []))]
    public function renderReturnsEmptyStringWhenNoUidAndNoUrl(): void
    {
        $this->viewHelper->setArguments([
            'label' => 'Test',
            'uid' => null,
            'table' => null,
            'url' => null,
            'icon' => null,
            'class' => '',
        ]);

        self::assertSame('', $this->viewHelper->render());
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(uc: []))]
    public function renderReturnsHiddenInputWithUid(): void
    {
        $this->viewHelper->setArguments([
            'label' => 'Test Label',
            'uid' => 42,
            'table' => 'tx_news_domain_model_news',
            'url' => null,
            'icon' => 'apps-pagetree-page-default',
            'class' => '',
        ]);

        $result = $this->viewHelper->render();

        self::assertStringContainsString('<input type="hidden"', $result);
        self::assertStringContainsString('class="frontend-edit__data', $result);
        self::assertStringContainsString('Test Label', $result);
        self::assertStringContainsString('42', $result);
        self::assertStringContainsString('tx_news_domain_model_news', $result);
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(uc: []))]
    public function renderReturnsHiddenInputWithUrl(): void
    {
        $this->viewHelper->setArguments([
            'label' => 'External Link',
            'uid' => null,
            'table' => null,
            'url' => 'https://example.com/edit',
            'icon' => null,
            'class' => '',
        ]);

        $result = $this->viewHelper->render();

        self::assertStringContainsString('<input type="hidden"', $result);
        // URL is JSON encoded (slashes escaped) and then HTML encoded
        self::assertStringContainsString('example.com', $result);
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(uc: []))]
    public function renderIncludesAdditionalClass(): void
    {
        $this->viewHelper->setArguments([
            'label' => 'Test',
            'uid' => 1,
            'table' => 'tt_content',
            'url' => null,
            'icon' => null,
            'class' => 'custom-class',
        ]);

        $result = $this->viewHelper->render();

        self::assertStringContainsString('class="frontend-edit__data custom-class"', $result);
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(uc: []))]
    public function renderEscapesClassArgument(): void
    {
        $this->viewHelper->setArguments([
            'label' => 'Test',
            'uid' => 1,
            'table' => 'tt_content',
            'url' => null,
            'icon' => null,
            'class' => 'x" onfocus="alert(1)',
        ]);

        $result = $this->viewHelper->render();

        self::assertStringContainsString('class="frontend-edit__data x&quot; onfocus=&quot;alert(1)"', $result);
        self::assertStringNotContainsString('" onfocus="', $result);
    }

    #[Test]
    #[WithGlobal('BE_USER', new FakeBackendUser(uc: []))]
    public function renderOutputIsValidJson(): void
    {
        $this->viewHelper->setArguments([
            'label' => 'Test',
            'uid' => 123,
            'table' => 'tt_content',
            'url' => null,
            'icon' => 'content-text',
            'class' => '',
        ]);

        $result = $this->viewHelper->render();

        // Extract the JSON value from the input
        preg_match('/value="([^"]+)"/', $result, $matches);
        self::assertNotEmpty($matches[1]);

        $jsonString = html_entity_decode($matches[1], \ENT_QUOTES);
        $decoded = json_decode($jsonString, true);

        self::assertIsArray($decoded);
        self::assertSame('Test', $decoded['label']);
        self::assertSame(123, $decoded['uid']);
        self::assertSame('tt_content', $decoded['table']);
    }
}
