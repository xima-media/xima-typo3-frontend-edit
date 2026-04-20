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

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Service\Menu\ContentElementButtonBuilder;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * ContentElementButtonBuilderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ContentElementButtonBuilder::class)]
final class ContentElementButtonBuilderTest extends TestCase
{
    private IconService $iconService;
    private UrlBuilderService $urlBuilderService;
    private UriBuilder $uriBuilderMock;

    protected function setUp(): void
    {
        $iconMock = $this->createMock(Icon::class);
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($iconMock);
        $this->iconService = new IconService($iconFactoryMock);

        $this->uriBuilderMock = $this->createMock(UriBuilder::class);
        $this->uriBuilderMock->method('buildUriFromRoute')->willReturn(new Uri('/typo3/record/edit'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $this->uriBuilderMock);
        $this->urlBuilderService = new UrlBuilderService();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['LANG']);
    }

    #[Test]
    public function createSimpleEditButtonReturnsLinkButton(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);

        $button = $builder->createSimpleEditButton(
            ['uid' => 1, 'CType' => 'text'],
            0,
            '/return',
            false,
        );

        self::assertSame(ButtonType::Link, $button->getType());
        self::assertNotNull($button->getUrl());
        self::assertStringContainsString('tx_ximatypo3frontendedit', $button->getUrl());
    }

    #[Test]
    public function createSimpleEditButtonSetsContextualUrl(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);

        $button = $builder->createSimpleEditButton(
            ['uid' => 1, 'CType' => 'text'],
            0,
            '/return',
            false,
            '/typo3/contextual',
        );

        self::assertSame('/typo3/contextual', $button->getContextualUrl());
    }

    #[Test]
    public function createSimpleEditButtonWithoutContextualUrlSetsNull(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);

        $button = $builder->createSimpleEditButton(
            ['uid' => 1, 'CType' => 'text'],
            0,
            '/return',
            false,
        );

        self::assertNull($button->getContextualUrl());
    }

    #[Test]
    public function createFullMenuButtonReturnsMenuButton(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);

        $button = $builder->createFullMenuButton();

        self::assertSame(ButtonType::Menu, $button->getType());
    }

    #[Test]
    public function addInfoSectionAddsChildrenToMenuButton(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $languageService = $this->createMock(\TYPO3\CMS\Core\Localization\LanguageService::class);
        $languageService->method('sL')->willReturn('Translated');
        $GLOBALS['LANG'] = $languageService;

        $builder->addInfoSection($menuButton, ['uid' => 1, 'CType' => 'text', 'header' => 'Test'], ['label' => 'Text', 'icon' => 'content-text']);

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_info', $children);
        self::assertArrayHasKey('info_header', $children);

        unset($GLOBALS['LANG']);
    }

    #[Test]
    public function addInfoSectionHandlesEmptyHeader(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $languageService = $this->createMock(\TYPO3\CMS\Core\Localization\LanguageService::class);
        $languageService->method('sL')->willReturn('Translated');
        $GLOBALS['LANG'] = $languageService;

        $builder->addInfoSection($menuButton, ['uid' => 1, 'CType' => 'text', 'header' => ''], ['label' => 'Text']);

        self::assertArrayHasKey('info_header', $menuButton->getChildren());

        unset($GLOBALS['LANG']);
    }

    #[Test]
    public function addInfoSectionHandlesMissingHeader(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $languageService = $this->createMock(\TYPO3\CMS\Core\Localization\LanguageService::class);
        $languageService->method('sL')->willReturn('Translated');
        $GLOBALS['LANG'] = $languageService;

        $builder->addInfoSection($menuButton, ['uid' => 1, 'CType' => 'text'], ['label' => 'Text']);

        self::assertArrayHasKey('info_header', $menuButton->getChildren());

        unset($GLOBALS['LANG']);
    }

    #[Test]
    public function addEditSectionAddsEditAndPageButtons(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addEditSection($menuButton, ['uid' => 1, 'CType' => 'text'], 0, 1, '/return');

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_edit', $children);
        self::assertArrayHasKey('edit', $children);
        self::assertArrayHasKey('edit_page', $children);
    }

    #[Test]
    public function addEditSectionSetsContextualUrlOnEditButton(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addEditSection($menuButton, ['uid' => 1, 'CType' => 'text'], 0, 1, '/return', '/typo3/contextual');

        self::assertSame('/typo3/contextual', $menuButton->getChildren()['edit']->getContextualUrl());
    }

    #[Test]
    public function addActionSectionAddsAllActionButtons(): void
    {
        $builder = new ContentElementButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addActionSection($menuButton, ['uid' => 1, 'pid' => 1], '/return');

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_action', $children);
        self::assertArrayHasKey('hide', $children);
        self::assertArrayHasKey('info', $children);
        self::assertArrayHasKey('move', $children);
        self::assertArrayHasKey('history', $children);
        self::assertArrayHasKey('new_content_after', $children);
    }
}
