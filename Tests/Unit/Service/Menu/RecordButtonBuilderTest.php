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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Menu;

use KonradMichalik\Ttt\Attribute\WithSingleton;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Service\Menu\RecordButtonBuilder;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;
use Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures\FakeUriBuilder;

/**
 * RecordButtonBuilderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(RecordButtonBuilder::class)]
#[WithSingleton(UriBuilder::class, new FakeUriBuilder())]
final class RecordButtonBuilderTest extends TestCase
{
    private IconService $iconService;
    private ?UrlBuilderService $urlBuilderService = null;

    protected function setUp(): void
    {
        $iconMock = $this->createMock(Icon::class);
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($iconMock);
        $this->iconService = new IconService($iconFactoryMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['LANG'], $GLOBALS['TCA']);
    }

    #[Test]
    public function addInfoSectionAddsChildrenToMenuButton(): void
    {
        $this->setUpGlobals();

        $builder = new RecordButtonBuilder($this->iconService, $this->urlBuilderService());
        $menuButton = new Button('Menu', ButtonType::Menu, null, null, false);

        $builder->addInfoSection($menuButton, 'tx_news_domain_model_news', ['uid' => 42, 'title' => 'Test News']);

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_info', $children);
        self::assertArrayHasKey('info_header', $children);
    }

    #[Test]
    public function addInfoSectionHandlesMissingTitle(): void
    {
        $this->setUpGlobals();

        $builder = new RecordButtonBuilder($this->iconService, $this->urlBuilderService());
        $menuButton = new Button('Menu', ButtonType::Menu, null, null, false);

        $builder->addInfoSection($menuButton, 'tx_news_domain_model_news', ['uid' => 42]);

        self::assertArrayHasKey('info_header', $menuButton->getChildren());
    }

    #[Test]
    public function addInfoSectionFallsBackToTableNameWhenNoTcaTitle(): void
    {
        // Real sL() returns a non-"LLL:"-prefixed string unchanged; only the
        // missing TCA ctrl.title (never passed to sL() at all, see addInfoSection's
        // `?? ''` fallback) is what should resolve to "empty", not the label itself.
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageService;

        $builder = new RecordButtonBuilder($this->iconService, $this->urlBuilderService());
        $menuButton = new Button('Menu', ButtonType::Menu, null, null, false);

        $builder->addInfoSection($menuButton, 'tx_news_domain_model_news', ['uid' => 42]);

        $label = $menuButton->getChildren()['info_header']->render()['label'];
        self::assertStringContainsString('tx_news_domain_model_news', $label);
    }

    #[Test]
    public function addEditSectionAddsOnlyOneEditButton(): void
    {
        $builder = new RecordButtonBuilder($this->iconService, $this->urlBuilderService());
        $menuButton = new Button('Menu', ButtonType::Menu, null, null, false);

        $builder->addEditSection($menuButton, 'tx_news_domain_model_news', 42, 0, '/return');

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_edit', $children);
        self::assertArrayHasKey('edit', $children);
        self::assertCount(2, $children);
    }

    #[Test]
    public function addEditSectionSetsContextualUrl(): void
    {
        $builder = new RecordButtonBuilder($this->iconService, $this->urlBuilderService());
        $menuButton = new Button('Menu', ButtonType::Menu, null, null, false);

        $builder->addEditSection($menuButton, 'tx_news_domain_model_news', 42, 0, '/return', '/typo3/contextual');

        self::assertSame('/typo3/contextual', $menuButton->getChildren()['edit']->getContextualUrl());
    }

    #[Test]
    public function addActionSectionAddsOnlyInfoAndHistoryButtons(): void
    {
        $builder = new RecordButtonBuilder($this->iconService, $this->urlBuilderService());
        $menuButton = new Button('Menu', ButtonType::Menu, null, null, false);

        $builder->addActionSection($menuButton, 'tx_news_domain_model_news', 42, '/return');

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_action', $children);
        self::assertArrayHasKey('info', $children);
        self::assertArrayHasKey('history', $children);
        self::assertArrayNotHasKey('hide', $children);
        self::assertArrayNotHasKey('delete', $children);
        self::assertArrayNotHasKey('move', $children);
    }

    /**
     * Lazily constructed: #[WithSingleton] only takes effect once the test method starts running, after setUp().
     */
    private function urlBuilderService(): UrlBuilderService
    {
        return $this->urlBuilderService ??= new UrlBuilderService();
    }

    private function setUpGlobals(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturn('News');
        $GLOBALS['LANG'] = $languageService;
        $GLOBALS['TCA']['tx_news_domain_model_news']['ctrl']['title'] = 'LLL:EXT:news/.../news.xlf:tab1';
        $GLOBALS['TCA']['tx_news_domain_model_news']['ctrl']['label'] = 'title';
    }
}
