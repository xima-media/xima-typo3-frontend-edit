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
use Xima\XimaTypo3FrontendEdit\Service\Menu\PageButtonBuilder;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * PageButtonBuilderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(PageButtonBuilder::class)]
final class PageButtonBuilderTest extends TestCase
{
    private IconService $iconService;
    private UrlBuilderService $urlBuilderService;

    protected function setUp(): void
    {
        $iconMock = $this->createMock(Icon::class);
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($iconMock);
        $this->iconService = new IconService($iconFactoryMock);

        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('buildUriFromRoute')->willReturn(new Uri('/typo3/mock'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);
        $this->urlBuilderService = new UrlBuilderService();
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

        $builder = new PageButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addInfoSection($menuButton, ['uid' => 1, 'title' => 'Test Page', 'doktype' => 1]);

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_info', $children);
        self::assertArrayHasKey('info_header', $children);
    }

    #[Test]
    public function addInfoSectionHandlesEmptyTitle(): void
    {
        $this->setUpGlobals();

        $builder = new PageButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addInfoSection($menuButton, ['uid' => 1, 'title' => '', 'doktype' => 1]);

        self::assertArrayHasKey('info_header', $menuButton->getChildren());
    }

    #[Test]
    public function addInfoSectionHandlesMissingTitle(): void
    {
        $this->setUpGlobals();

        $builder = new PageButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addInfoSection($menuButton, ['uid' => 1, 'doktype' => 1]);

        self::assertArrayHasKey('info_header', $menuButton->getChildren());
    }

    #[Test]
    public function addInfoSectionHandlesMissingDoktype(): void
    {
        $this->setUpGlobals();

        $builder = new PageButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addInfoSection($menuButton, ['uid' => 1]);

        self::assertArrayHasKey('info_header', $menuButton->getChildren());
    }

    #[Test]
    public function addEditSectionAddsEditButtons(): void
    {
        $builder = new PageButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addEditSection($menuButton, 1, 0, '/return');

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_edit', $children);
        self::assertArrayHasKey('edit_page_properties', $children);
        self::assertArrayHasKey('edit_page', $children);
    }

    #[Test]
    public function addEditSectionSetsContextualUrl(): void
    {
        $builder = new PageButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addEditSection($menuButton, 1, 0, '/return', '/typo3/contextual');

        self::assertSame('/typo3/contextual', $menuButton->getChildren()['edit_page_properties']->getContextualUrl());
    }

    #[Test]
    public function addActionSectionAddsInfoAndHistoryButtons(): void
    {
        $builder = new PageButtonBuilder($this->iconService, $this->urlBuilderService);
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addActionSection($menuButton, 1, '/return');

        $children = $menuButton->getChildren();
        self::assertArrayHasKey('div_action', $children);
        self::assertArrayHasKey('info', $children);
        self::assertArrayHasKey('history', $children);
    }

    private function setUpGlobals(): void
    {
        $GLOBALS['LANG'] = new class {
            public function sL(string $key): string
            {
                return 'Translated';
            }
        };
        $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] = [
            ['label' => 'Standard', 'value' => 1],
        ];
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'] = [
            1 => 'apps-pagetree-page-default',
        ];
    }
}
