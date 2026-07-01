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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Service\Menu\AbstractMenuButtonBuilder;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * AbstractMenuButtonBuilderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(AbstractMenuButtonBuilder::class)]
final class AbstractMenuButtonBuilderTest extends TestCase
{
    private Icon $iconMock;

    private IconService $iconService;

    protected function setUp(): void
    {
        $this->iconMock = $this->createMock(Icon::class);
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($this->iconMock);
        $this->iconService = new IconService($iconFactoryMock);

        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('buildUriFromRoute')->willReturn(new Uri('/typo3/mock'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['LANG']);
    }

    #[Test]
    public function addButtonAppendsButtonWithProvidedValues(): void
    {
        $builder = $this->createBuilder();
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addButton($menuButton, 'edit', ButtonType::Link, 'Edit label', '/edit', 'actions-edit');

        $child = $menuButton->getChildren()['edit'];
        self::assertSame('Edit label', $child->getLabel());
        self::assertSame(ButtonType::Link, $child->getType());
        self::assertSame('/edit', $child->getUrl());
        self::assertSame($this->iconMock, $child->getIcon());
    }

    #[Test]
    public function addButtonUsesFallbackLabelWhenLabelMissing(): void
    {
        $builder = $this->createBuilder();
        $menuButton = new Button('Menu', ButtonType::Menu);

        $builder->addButton($menuButton, 'delete', ButtonType::Link);

        $child = $menuButton->getChildren()['delete'];
        self::assertStringContainsString('locallang.xlf:delete', $child->getLabel());
        self::assertNull($child->getIcon());
        self::assertNull($child->getUrl());
    }

    #[Test]
    public function getLanguageServiceReturnsGlobalLanguageService(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;

        $builder = $this->createBuilder();

        self::assertSame($languageService, $builder->exposeLanguageService());
    }

    private function createBuilder(): TestableMenuButtonBuilder
    {
        return new TestableMenuButtonBuilder($this->iconService, new UrlBuilderService());
    }
}

/**
 * TestableMenuButtonBuilder.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class TestableMenuButtonBuilder extends AbstractMenuButtonBuilder
{
    public function exposeLanguageService(): LanguageService
    {
        return $this->getLanguageService();
    }
}
