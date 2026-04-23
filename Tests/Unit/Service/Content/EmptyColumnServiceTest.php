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
use Xima\XimaTypo3FrontendEdit\Service\Content\EmptyColumnService;

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

    private UriBuilder $uriBuilder;

    private LanguageServiceFactory $languageServiceFactory;

    protected function setUp(): void
    {
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->uriBuilder = $this->createMock(UriBuilder::class);
        $this->languageServiceFactory = $this->createMock(LanguageServiceFactory::class);

        // Mock schema manager to simulate missing tx_container_parent field
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $schemaManager->method('listTableColumns')->willReturn([]);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $this->connectionPool->method('getConnectionForTable')->willReturn($connection);
    }

    #[Test]
    public function getEmptyColumnsReturnsEmptyArrayWhenAllColumnsHaveContent(): void
    {
        $this->mockQueryBuilderWithCount(1);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->uriBuilder,
            $this->languageServiceFactory,
        );

        $result = $service->getEmptyColumns(1, 0, '/return');

        self::assertSame([], $result);
    }

    #[Test]
    public function getEmptyColumnsReturnsColumnsWithNewContentUrl(): void
    {
        $this->mockQueryBuilderWithCount(0);
        $this->uriBuilder->method('buildUriFromRoute')->willReturn(new Uri('/typo3/record/edit'));

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->uriBuilder,
            $this->languageServiceFactory,
        );

        $result = $service->getEmptyColumns(1, 0, '/return');

        self::assertNotEmpty($result);
        self::assertArrayHasKey('colPos', $result[0]);
        self::assertArrayHasKey('newContentUrl', $result[0]);
        self::assertArrayHasKey('name', $result[0]);
        self::assertSame('/typo3/record/edit', $result[0]['newContentUrl']);
    }

    #[Test]
    public function getEmptyColumnsIgnoresContainerMarkersWithoutContainerField(): void
    {
        $this->mockQueryBuilderWithCount(1);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->uriBuilder,
            $this->languageServiceFactory,
        );

        $requestData = [
            '_containerMarkers' => [
                42 => [200, 201],
            ],
        ];

        $result = $service->getEmptyColumns(1, 0, '/return', $requestData);

        // Container markers should be ignored since tx_container_parent field does not exist
        self::assertSame([], $result);
    }

    #[Test]
    public function getEmptyColumnsHandlesContainerMarkersWhenFieldExists(): void
    {
        // Mock schema manager to simulate tx_container_parent field exists
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $column = $this->createMock(\Doctrine\DBAL\Schema\Column::class);
        $schemaManager->method('listTableColumns')->willReturn(['tx_container_parent' => $column]);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        // All columns empty
        $this->mockQueryBuilderWithCountForPool($connectionPool, 0);
        $this->uriBuilder->method('buildUriFromRoute')->willReturn(new Uri('/typo3/record/edit'));

        $service = new EmptyColumnService(
            $connectionPool,
            $this->uriBuilder,
            $this->languageServiceFactory,
        );

        $requestData = [
            '_containerMarkers' => [
                42 => [200],
            ],
        ];

        $result = $service->getEmptyColumns(1, 0, '/return', $requestData);

        $containerResults = array_filter($result, static fn (array $r): bool => isset($r['containerUid']));
        self::assertNotEmpty($containerResults);
        $first = array_values($containerResults)[0];
        self::assertSame(200, $first['colPos']);
        self::assertSame(42, $first['containerUid']);
    }

    #[Test]
    public function getEmptyColumnsFiltersInvalidContainerMarkers(): void
    {
        // Mock schema manager with container field
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $column = $this->createMock(\Doctrine\DBAL\Schema\Column::class);
        $schemaManager->method('listTableColumns')->willReturn(['tx_container_parent' => $column]);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        // Keep page columns non-empty, but make any unexpected container lookup observable
        $this->mockQueryBuilderWithCountForPool($connectionPool, 1);
        $this->uriBuilder
            ->expects(self::never())
            ->method('buildUriFromRoute');

        $service = new EmptyColumnService(
            $connectionPool,
            $this->uriBuilder,
            $this->languageServiceFactory,
        );

        $requestData = [
            '_containerMarkers' => [
                0 => [200],       // uid 0 should be skipped
                -5 => [100],      // negative uid should be skipped
                'abc' => [100],   // non-numeric should be cast to 0 and skipped
            ],
        ];

        $result = $service->getEmptyColumns(1, 0, '/return', $requestData);

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
