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
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Service\Menu\{PageButtonBuilder, PageMenuGenerator};
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};

/**
 * PageMenuGeneratorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(PageMenuGenerator::class)]
final class PageMenuGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('buildUriFromRoute')->willReturn(new Uri('/typo3/mock'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function getDropdownReturnsEmptyArrayWhenRoutingIsNull(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturnMap([
            ['routing', null, null],
            ['language', null, null],
        ]);

        $generator = $this->createGenerator();
        $result = $generator->getDropdown($request);

        self::assertSame([], $result);
    }

    #[Test]
    public function getDropdownReturnsEmptyArrayWhenRoutingIsNotPageArguments(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturnMap([
            ['routing', null, new stdClass()],
            ['language', null, null],
        ]);

        $generator = $this->createGenerator();
        $result = $generator->getDropdown($request);

        self::assertSame([], $result);
    }

    #[Test]
    public function getDropdownReturnsEmptyArrayWhenPageIdIsZero(): void
    {
        $routing = $this->createMock(PageArguments::class);
        $routing->method('getPageId')->willReturn(0);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturnMap([
            ['routing', null, $routing],
            ['language', null, null],
        ]);

        $generator = $this->createGenerator();
        $result = $generator->getDropdown($request);

        self::assertSame([], $result);
    }

    private function createGenerator(): PageMenuGenerator
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($this->createMock(Icon::class));
        $iconService = new IconService($iconFactoryMock);
        $urlBuilderService = new UrlBuilderService();
        $pageButtonBuilder = new PageButtonBuilder($iconService, $urlBuilderService);

        return new PageMenuGenerator(
            $pageButtonBuilder,
            $this->createMock(EventDispatcher::class),
            $this->createMock(ExtensionConfiguration::class),
            $this->createMock(ConnectionPool::class),
            $urlBuilderService,
        );
    }
}
