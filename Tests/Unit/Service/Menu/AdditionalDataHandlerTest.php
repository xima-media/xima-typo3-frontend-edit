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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\{Expression\ExpressionBuilder, QueryBuilder, Restriction\DefaultRestrictionContainer};
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Repository\ContentElementRepository;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\AdditionalDataHandler;
use Xima\XimaTypo3FrontendEdit\Service\Ui\{IconService, UrlBuilderService};
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * AdditionalDataHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(AdditionalDataHandler::class)]
final class AdditionalDataHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->method('buildUriFromRoute')->willReturn(new Uri('/typo3/mock'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilder);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['BE_USER'], $GLOBALS['TCA']);
    }

    #[Test]
    public function handleDataDoesNothingForEmptyEntries(): void
    {
        $handler = $this->createHandler(new BackendUserService());
        $button = $this->createButton();

        $handler->handleData($button, [], ['icon' => 'content-text'], 0, '#anchor');

        self::assertSame([], $button->getChildren());
    }

    #[Test]
    public function handleDataAddsDividerAndSkipsInvalidEntries(): void
    {
        $handler = $this->createHandler(new BackendUserService());
        $button = $this->createButton();

        $entries = [
            ['label' => null, 'table' => 'pages', 'uid' => 1, 'url' => null, 'icon' => null],
            ['label' => '', 'table' => 'pages', 'uid' => 1, 'url' => null, 'icon' => null],
            ['label' => 'No target', 'table' => null, 'uid' => null, 'url' => null, 'icon' => null],
        ];

        $handler->handleData($button, $entries, ['icon' => 'content-text'], 0, '#anchor');

        $children = $button->getChildren();
        self::assertArrayHasKey('div_data', $children);
        self::assertCount(1, $children);
        self::assertSame(ButtonType::Divider, $children['div_data']->getType());
    }

    #[Test]
    public function handleDataSkipsEntryWithoutTableAndUidResolution(): void
    {
        $handler = $this->createHandler(new BackendUserService());
        $button = $this->createButton();

        $entries = [
            ['label' => 'Url only', 'table' => null, 'uid' => null, 'url' => '/some/url', 'icon' => null],
        ];

        $handler->handleData($button, $entries, ['icon' => 'content-text'], 0, '#anchor');

        $children = $button->getChildren();
        self::assertArrayHasKey('div_data', $children);
        self::assertArrayNotHasKey('data_0', $children);
    }

    #[Test]
    public function handleDataSkipsEntryWhenNoEditAccess(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        if (method_exists(BackendUserAuthentication::class, 'checkRecordEditAccess')) {
            $backendUser->method('checkRecordEditAccess')->willReturn(new \TYPO3\CMS\Core\Authentication\AccessCheckResult(false));
        }
        $backendUser->method('recordEditAccessInternals')->willReturn(false);
        $GLOBALS['BE_USER'] = $backendUser;

        $handler = $this->createHandler(new BackendUserService());
        $button = $this->createButton();

        $entries = [
            ['label' => 'Record', 'table' => 'pages', 'uid' => 5, 'url' => null, 'icon' => null],
        ];

        $handler->handleData($button, $entries, ['icon' => 'content-text'], 0, '#anchor');

        $children = $button->getChildren();
        self::assertArrayHasKey('div_data', $children);
        self::assertArrayNotHasKey('data_0', $children);
    }

    #[Test]
    public function handleDataAddsButtonForResolvedRecord(): void
    {
        $this->registerBackendUserWithAccess();
        $this->registerRecordLookup(['uid' => 5, 'sys_language_uid' => 0]);

        $handler = $this->createHandler(new BackendUserService());
        $button = $this->createButton();

        $entries = [
            ['label' => 'Record', 'table' => 'pages', 'uid' => 5, 'url' => null, 'icon' => 'content-image'],
        ];

        $handler->handleData($button, $entries, ['icon' => 'content-text'], 0, '#anchor');

        $children = $button->getChildren();
        self::assertArrayHasKey('data_0', $children);
        self::assertSame(ButtonType::Link, $children['data_0']->getType());
    }

    #[Test]
    public function handleDataUsesEntryUrlWhenPresent(): void
    {
        $this->registerBackendUserWithAccess();
        $this->registerRecordLookup(['uid' => 5, 'sys_language_uid' => 0]);

        $handler = $this->createHandler(new BackendUserService());
        $button = $this->createButton();

        $entries = [
            ['label' => 'Record', 'table' => 'pages', 'uid' => 5, 'url' => '/custom/url', 'icon' => null],
        ];

        $handler->handleData($button, $entries, ['icon' => 'content-text'], 0, '#anchor');

        self::assertSame('/custom/url', $button->getChildren()['data_0']->getUrl());
    }

    #[Test]
    public function handleDataSkipsWhenRecordNotFound(): void
    {
        $this->registerBackendUserWithAccess();
        $this->registerRecordLookup(false);

        $handler = $this->createHandler(new BackendUserService());
        $button = $this->createButton();

        $entries = [
            ['label' => 'Record', 'table' => 'pages', 'uid' => 5, 'url' => null, 'icon' => null],
        ];

        $handler->handleData($button, $entries, ['icon' => 'content-text'], 0, '#anchor');

        self::assertArrayNotHasKey('data_0', $button->getChildren());
    }

    #[Test]
    public function handleDataResolvesTranslatedRecord(): void
    {
        $this->registerBackendUserWithAccess();
        $this->registerRecordLookup(['uid' => 5, 'sys_language_uid' => 0]);

        $repository = new ContentElementRepository(
            $this->createConnectionPoolReturning(['uid' => 99, 'sys_language_uid' => 1]),
        );

        $handler = new AdditionalDataHandler(
            new BackendUserService(),
            new UrlBuilderService(),
            new IconService($this->createIconFactory()),
            $repository,
        );
        $button = $this->createButton();

        $entries = [
            ['label' => 'Record', 'table' => 'pages', 'uid' => 5, 'url' => null, 'icon' => null],
        ];

        $handler->handleData($button, $entries, ['icon' => 'content-text'], 1, '#anchor');

        self::assertArrayHasKey('data_0', $button->getChildren());
    }

    #[Test]
    public function handleDataSkipsWhenTranslationMissing(): void
    {
        $this->registerBackendUserWithAccess();
        $this->registerRecordLookup(['uid' => 5, 'sys_language_uid' => 0]);

        $repository = new ContentElementRepository(
            $this->createConnectionPoolReturning(false),
        );

        $handler = new AdditionalDataHandler(
            new BackendUserService(),
            new UrlBuilderService(),
            new IconService($this->createIconFactory()),
            $repository,
        );
        $button = $this->createButton();

        $entries = [
            ['label' => 'Record', 'table' => 'pages', 'uid' => 5, 'url' => null, 'icon' => null],
        ];

        $handler->handleData($button, $entries, ['icon' => 'content-text'], 1, '#anchor');

        self::assertArrayNotHasKey('data_0', $button->getChildren());
    }

    private function registerBackendUserWithAccess(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => 1];
        if (method_exists(BackendUserAuthentication::class, 'checkRecordEditAccess')) {
            $backendUser->method('checkRecordEditAccess')->willReturn(new \TYPO3\CMS\Core\Authentication\AccessCheckResult(true));
        }
        $backendUser->method('recordEditAccessInternals')->willReturn(true);
        $GLOBALS['BE_USER'] = $backendUser;
    }

    /**
     * @param array<string, mixed>|false $record
     */
    private function registerRecordLookup(array|false $record): void
    {
        $GLOBALS['TCA']['pages'] = ['ctrl' => []];
        $this->registerTcaSchemaFactory();
        GeneralUtility::addInstance(
            ConnectionPool::class,
            $this->createConnectionPoolReturning($record),
        );
    }

    private function registerTcaSchemaFactory(): void
    {
        if (!class_exists(\TYPO3\CMS\Core\Schema\TcaSchemaFactory::class)) {
            return;
        }

        $schemaFactory = $this->createMock(\TYPO3\CMS\Core\Schema\TcaSchemaFactory::class);
        $schemaFactory->method('has')->willReturn(true);
        $schemaFactory->method('get')->willReturn($this->createMock(\TYPO3\CMS\Core\Schema\TcaSchema::class));
        GeneralUtility::addInstance(\TYPO3\CMS\Core\Schema\TcaSchemaFactory::class, $schemaFactory);
        GeneralUtility::addInstance(
            \TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction::class,
            $this->createMock(\TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction::class),
        );
    }

    /**
     * @param array<string, mixed>|false $record
     */
    private function createConnectionPoolReturning(array|false $record): ConnectionPool
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAssociative')->willReturn($record);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRestrictions')->willReturn($this->createMock(DefaultRestrictionContainer::class));
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($this->createMock(ExpressionBuilder::class));
        $queryBuilder->method('createNamedParameter')->willReturn(':dcValue');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        return $connectionPool;
    }

    private function createIconFactory(): IconFactory
    {
        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->method('getIcon')->willReturn($this->createMock(Icon::class));

        return $iconFactory;
    }

    private function createButton(): Button
    {
        return new Button('root', ButtonType::Link);
    }

    private function createHandler(BackendUserService $backendUserService): AdditionalDataHandler
    {
        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->method('getIcon')->willReturn($this->createMock(Icon::class));

        return new AdditionalDataHandler(
            $backendUserService,
            new UrlBuilderService(),
            new IconService($iconFactory),
            new ContentElementRepository($this->createMock(ConnectionPool::class)),
        );
    }
}
