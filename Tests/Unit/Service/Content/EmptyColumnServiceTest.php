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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Content;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};
use TYPO3\CMS\Core\Database\Query\{Expression\ExpressionBuilder, QueryBuilder};
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Service\Content\EmptyColumnService;
use Xima\XimaTypo3FrontendEdit\Service\Ui\UrlBuilderService;

/**
 * EmptyColumnServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(EmptyColumnService::class)]
final class EmptyColumnServiceTest extends TestCase
{
    private ConnectionPool $connectionPool;

    private UrlBuilderService $urlBuilderService;

    private LanguageServiceFactory $languageServiceFactory;

    protected function setUp(): void
    {
        // Mock UriBuilder singleton so real UrlBuilderService picks it up
        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('buildUriFromRoute')
            ->willReturn(new Uri('/typo3/record/content/wizard/new'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);

        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->urlBuilderService = new UrlBuilderService();
        $this->languageServiceFactory = $this->createMock(LanguageServiceFactory::class);

        // Mock schema manager to simulate missing tx_container_parent field
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $table = $this->createMock(\Doctrine\DBAL\Schema\Table::class);
        $table->method('hasColumn')->willReturn(false);
        $schemaManager->method('introspectTableByUnquotedName')->willReturn($table);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function getColumnTargetsMarksFilledColumnsAsNotEmpty(): void
    {
        $this->mockQueryBuilderWithCount(3);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService,
            $this->languageServiceFactory,
        );

        $result = $service->getColumnTargets(1, 0, '/return');

        self::assertNotEmpty($result);
        self::assertFalse($result[0]['isEmpty']);
        self::assertArrayHasKey('newContentUrl', $result[0]);
    }

    #[Test]
    public function getColumnTargetsMarksEmptyColumnsAsEmpty(): void
    {
        $this->mockQueryBuilderWithCount(0);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService,
            $this->languageServiceFactory,
        );

        $result = $service->getColumnTargets(1, 0, '/return');

        self::assertNotEmpty($result);
        self::assertTrue($result[0]['isEmpty']);
    }

    #[Test]
    public function getColumnTargetsOmitsFilledColumnsWhenInsertButtonsEnabled(): void
    {
        $this->mockQueryBuilderWithCount(3);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService,
            $this->languageServiceFactory,
        );

        $result = $service->getColumnTargets(1, 0, '/return', [], true);

        // Filled columns are redundant with the per-element "insert after" buttons
        self::assertEmpty($result);
    }

    #[Test]
    public function getColumnTargetsKeepsEmptyColumnsWhenInsertButtonsEnabled(): void
    {
        $this->mockQueryBuilderWithCount(0);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService,
            $this->languageServiceFactory,
        );

        $result = $service->getColumnTargets(1, 0, '/return', [], true);

        // Empty columns have no elements, so they still need their own button
        self::assertNotEmpty($result);
        self::assertTrue($result[0]['isEmpty']);
    }

    #[Test]
    public function getColumnTargetsIgnoresContainerMarkersWithoutContainerField(): void
    {
        $this->mockQueryBuilderWithCount(1);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService,
            $this->languageServiceFactory,
        );

        $requestData = [
            '_containerMarkers' => [
                42 => [200, 201],
            ],
        ];

        $result = $service->getColumnTargets(1, 0, '/return', $requestData);

        // Container markers should be ignored since tx_container_parent field does not exist
        $containerResults = array_filter($result, static fn (array $r): bool => isset($r['containerUid']));
        self::assertEmpty($containerResults);
    }

    #[Test]
    public function getColumnTargetsHandlesContainerMarkersWhenFieldExists(): void
    {
        // Mock schema manager to simulate tx_container_parent field exists
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $table = $this->createMock(\Doctrine\DBAL\Schema\Table::class);
        $table->method('hasColumn')->willReturn(true);
        $schemaManager->method('introspectTableByUnquotedName')->willReturn($table);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        // All columns empty
        $this->mockQueryBuilderWithCountForPool($connectionPool, 0);

        $service = new EmptyColumnService(
            $connectionPool,
            $this->urlBuilderService,
            $this->languageServiceFactory,
        );

        $requestData = [
            '_containerMarkers' => [
                42 => [200],
            ],
        ];

        $result = $service->getColumnTargets(1, 0, '/return', $requestData);

        $containerResults = array_filter($result, static fn (array $r): bool => isset($r['containerUid']));
        self::assertNotEmpty($containerResults);
        $first = array_values($containerResults)[0];
        self::assertSame(200, $first['colPos']);
        self::assertSame(42, $first['containerUid']);
        self::assertTrue($first['isEmpty']);
    }

    #[Test]
    public function getColumnTargetsFiltersInvalidContainerMarkers(): void
    {
        // Mock schema manager with container field
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $table = $this->createMock(\Doctrine\DBAL\Schema\Table::class);
        $table->method('hasColumn')->willReturn(true);
        $schemaManager->method('introspectTableByUnquotedName')->willReturn($table);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        // Keep page columns non-empty
        $this->mockQueryBuilderWithCountForPool($connectionPool, 1);

        $service = new EmptyColumnService(
            $connectionPool,
            $this->urlBuilderService,
            $this->languageServiceFactory,
        );

        $requestData = [
            '_containerMarkers' => [
                0 => [200],       // uid 0 should be skipped
                -5 => [100],      // negative uid should be skipped
                'abc' => [100],   // non-numeric should be cast to 0 and skipped
            ],
        ];

        $result = $service->getColumnTargets(1, 0, '/return', $requestData);

        $containerResults = array_filter($result, static fn (array $r): bool => isset($r['containerUid']));
        self::assertEmpty($containerResults);
    }

    private function mockQueryBuilderWithCount(int $count): void
    {
        $this->mockQueryBuilderWithCountForPool($this->connectionPool, $count);
    }

    private function mockQueryBuilderWithCountForPool(ConnectionPool $connectionPool, int $count): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $result = $this->createMock(Result::class);

        $expressionBuilder->method('eq')->willReturn('');
        $expressionBuilder->method('in')->willReturn('');
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('count')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('createNamedParameter')->willReturnCallback(static fn (mixed $value): string => (string) $value);
        $queryBuilder->method('executeQuery')->willReturn($result);
        $result->method('fetchOne')->willReturn($count);

        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
    }
}
