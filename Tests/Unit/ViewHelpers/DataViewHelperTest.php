<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use stdClass;
use Xima\XimaTypo3FrontendEdit\Configuration;
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
    public function renderReturnsEmptyStringWhenNoBackendUser(): void
    {
        $GLOBALS['BE_USER'] = null;

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

    #[Test]
    public function renderReturnsEmptyStringWhenFrontendEditDisabled(): void
    {
        $GLOBALS['BE_USER'] = new stdClass();
        $GLOBALS['BE_USER']->uc = [Configuration::UC_KEY_DISABLED => true];

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

    #[Test]
    public function renderReturnsEmptyStringWhenNoUidAndNoUrl(): void
    {
        $GLOBALS['BE_USER'] = new stdClass();
        $GLOBALS['BE_USER']->uc = [];

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
    public function renderReturnsHiddenInputWithUid(): void
    {
        $GLOBALS['BE_USER'] = new stdClass();
        $GLOBALS['BE_USER']->uc = [];

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
    public function renderReturnsHiddenInputWithUrl(): void
    {
        $GLOBALS['BE_USER'] = new stdClass();
        $GLOBALS['BE_USER']->uc = [];

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
    public function renderIncludesAdditionalClass(): void
    {
        $GLOBALS['BE_USER'] = new stdClass();
        $GLOBALS['BE_USER']->uc = [];

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
    public function renderOutputIsValidJson(): void
    {
        $GLOBALS['BE_USER'] = new stdClass();
        $GLOBALS['BE_USER']->uc = [];

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
