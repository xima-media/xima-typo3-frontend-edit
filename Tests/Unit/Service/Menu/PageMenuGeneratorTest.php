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

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditPageDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Service\Menu\{PageButtonBuilder, PageMenuGenerator};
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

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
        unset($GLOBALS['LANG'], $GLOBALS['TCA']);
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

    #[Test]
    public function getDropdownReturnsRenderedMenuForPageWithRecord(): void
    {
        $this->setUpLanguageService();
        $this->setUpTca();

        $connectionPool = $this->createConnectionPoolReturning(['uid' => 5, 'title' => 'My Page', 'doktype' => 1]);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown($this->createRequest(5));

        self::assertArrayHasKey('label', $result);
        self::assertArrayHasKey('children', $result);
        self::assertArrayHasKey('info_header', $result['children']);
        self::assertArrayHasKey('edit_page_properties', $result['children']);
        self::assertArrayHasKey('targetBlank', $result);
    }

    #[Test]
    public function getDropdownSkipsInfoSectionWhenPageRecordNotFound(): void
    {
        $this->setUpLanguageService();
        $this->setUpTca();

        $connectionPool = $this->createConnectionPoolReturning(false);
        $generator = $this->createGenerator($connectionPool);

        $result = $generator->getDropdown($this->createRequest(9));

        self::assertArrayHasKey('children', $result);
        self::assertArrayNotHasKey('info_header', $result['children']);
        self::assertArrayHasKey('edit_page_properties', $result['children']);
    }

    #[Test]
    public function getDropdownRespectsEventListenerModifiedButton(): void
    {
        $this->setUpLanguageService();
        $this->setUpTca();

        $connectionPool = $this->createConnectionPoolReturning(['uid' => 3, 'title' => 'Page', 'doktype' => 1]);

        $replacement = new Button('replaced', ButtonType::Link, '/replaced-page');
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (FrontendEditPageDropdownModifyEvent $event) use ($replacement): FrontendEditPageDropdownModifyEvent {
                $event->setMenuButton($replacement);

                return $event;
            },
        );

        $generator = $this->createGenerator($connectionPool, $eventDispatcher);
        $result = $generator->getDropdown($this->createRequest(3));

        self::assertSame('/replaced-page', $result['url']);
        self::assertArrayHasKey('targetBlank', $result);
    }

    private function createRequest(int $pageId): ServerRequestInterface
    {
        $routing = $this->createMock(PageArguments::class);
        $routing->method('getPageId')->willReturn($pageId);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturnMap([
            ['routing', null, $routing],
            ['language', null, null],
        ]);
        $request->method('getUri')->willReturn(new Uri('https://example.com/page'));

        return $request;
    }

    private function setUpLanguageService(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageService;
    }

    private function setUpTca(): void
    {
        $GLOBALS['TCA'] = [
            'pages' => [
                'ctrl' => [
                    'typeicon_classes' => [1 => 'apps-pagetree-page-default'],
                ],
                'columns' => [
                    'doktype' => [
                        'config' => [
                            'items' => [
                                ['value' => 1, 'label' => 'Standard'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed>|false $record
     */
    private function createConnectionPoolReturning(array|false $record): ConnectionPool
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn($record);

        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->method('eq')->willReturn('eq');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('createNamedParameter')->willReturn('param');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        return $connectionPool;
    }

    private function createGenerator(
        ?ConnectionPool $connectionPool = null,
        ?EventDispatcher $eventDispatcher = null,
    ): PageMenuGenerator {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIcon')->willReturn($this->createMock(Icon::class));
        $iconService = new IconService($iconFactoryMock);
        $urlBuilderService = new UrlBuilderService();
        $pageButtonBuilder = new PageButtonBuilder($iconService, $urlBuilderService);

        if (null === $eventDispatcher) {
            $eventDispatcher = $this->createMock(EventDispatcher::class);
            $eventDispatcher->method('dispatch')->willReturnArgument(0);
        }

        return new PageMenuGenerator(
            $pageButtonBuilder,
            $eventDispatcher,
            $this->createMock(ExtensionConfiguration::class),
            $connectionPool ?? $this->createMock(ConnectionPool::class),
            $urlBuilderService,
        );
    }
}
