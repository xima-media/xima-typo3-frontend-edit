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
use KonradMichalik\Ttt\Attribute\WithSingleton;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\{Expression\ExpressionBuilder, QueryBuilder};
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Service\Content\EmptyColumnService;
use Xima\XimaTypo3FrontendEdit\Service\Ui\UrlBuilderService;
use Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures\FakeUriBuilder;

use function is_array;

/**
 * EmptyColumnServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(EmptyColumnService::class)]
#[WithSingleton(UriBuilder::class, new FakeUriBuilder(new Uri('/typo3/record/content/wizard/new')))]
final class EmptyColumnServiceTest extends TestCase
{
    private ConnectionPool $connectionPool;

    private ?UrlBuilderService $urlBuilderService = null;

    private LanguageServiceFactory $languageServiceFactory;

    protected function setUp(): void
    {
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->languageServiceFactory = $this->createMock(LanguageServiceFactory::class);

        // Default: EXT:container not installed (tx_container_parent absent from TCA)
        unset($GLOBALS['TCA']['tt_content']);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['TCA']['tt_content']);
    }

    #[Test]
    public function getColumnTargetsMarksFilledColumnsAsNotEmpty(): void
    {
        $this->mockGroupedCount($this->connectionPool, 3);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService(),
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
        $this->mockGroupedCount($this->connectionPool, 0);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService(),
            $this->languageServiceFactory,
        );

        $result = $service->getColumnTargets(1, 0, '/return');

        self::assertNotEmpty($result);
        self::assertTrue($result[0]['isEmpty']);
    }

    #[Test]
    public function getColumnTargetsOmitsFilledColumnsWhenInsertButtonsEnabled(): void
    {
        $this->mockGroupedCount($this->connectionPool, 3);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService(),
            $this->languageServiceFactory,
        );

        $result = $service->getColumnTargets(1, 0, '/return', [], true);

        // Filled columns are redundant with the per-element "insert after" buttons
        self::assertEmpty($result);
    }

    #[Test]
    public function getColumnTargetsKeepsEmptyColumnsWhenInsertButtonsEnabled(): void
    {
        $this->mockGroupedCount($this->connectionPool, 0);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService(),
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
        $this->mockGroupedCount($this->connectionPool, 1);

        $service = new EmptyColumnService(
            $this->connectionPool,
            $this->urlBuilderService(),
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
        $this->registerContainerField();

        // All columns empty (grouped query returns no rows)
        $connectionPool = $this->createMock(ConnectionPool::class);
        $this->mockGroupedCount($connectionPool, 0);

        $service = new EmptyColumnService(
            $connectionPool,
            $this->urlBuilderService(),
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
    public function getColumnTargetsMarksFilledContainerColumnAsNotEmpty(): void
    {
        $this->registerContainerField();

        $connectionPool = $this->createMock(ConnectionPool::class);
        // Container column 200 of container 42 holds one element
        $this->mockGroupedCountForPool(
            $connectionPool,
            [['colPos' => 0, 'tx_container_parent' => 0, 'cnt' => 0]],
            [['colPos' => 200, 'tx_container_parent' => 42, 'cnt' => 1]],
        );

        $service = new EmptyColumnService(
            $connectionPool,
            $this->urlBuilderService(),
            $this->languageServiceFactory,
        );

        $requestData = [
            '_containerMarkers' => [
                42 => [200],
            ],
        ];

        $result = $service->getColumnTargets(1, 0, '/return', $requestData);

        $containerResults = array_values(array_filter($result, static fn (array $r): bool => isset($r['containerUid'])));
        self::assertNotEmpty($containerResults);
        self::assertFalse($containerResults[0]['isEmpty']);
    }

    #[Test]
    public function getColumnTargetsFiltersInvalidContainerMarkers(): void
    {
        $this->registerContainerField();

        $connectionPool = $this->createMock(ConnectionPool::class);
        // Keep page columns non-empty
        $this->mockGroupedCount($connectionPool, 1);

        $service = new EmptyColumnService(
            $connectionPool,
            $this->urlBuilderService(),
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

    /**
     * Lazily constructed: #[WithSingleton] only takes effect once the test method starts running, after setUp().
     */
    private function urlBuilderService(): UrlBuilderService
    {
        return $this->urlBuilderService ??= new UrlBuilderService();
    }

    private function registerContainerField(): void
    {
        $GLOBALS['TCA']['tt_content']['columns']['tx_container_parent'] = ['config' => ['type' => 'passthrough']];
    }

    private function mockGroupedCount(ConnectionPool $connectionPool, int $count): void
    {
        $this->mockGroupedCountForPool(
            $connectionPool,
            [['colPos' => 0, 'tx_container_parent' => 0, 'cnt' => $count]],
        );
    }

    /**
     * @param list<array<string, int>>      $pageRows      grouped rows for the page-column query
     * @param list<array<string, int>>|null $containerRows grouped rows for the container-column query
     */
    private function mockGroupedCountForPool(ConnectionPool $connectionPool, array $pageRows, ?array $containerRows = null): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $result = $this->createMock(Result::class);

        $expressionBuilder->method('eq')->willReturn('');
        $expressionBuilder->method('in')->willReturn('');
        $expressionBuilder->method('count')->willReturn('COUNT(`uid`) AS `cnt`');
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('addSelectLiteral')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('groupBy')->willReturn($queryBuilder);
        $queryBuilder->method('createNamedParameter')->willReturnCallback(static fn (mixed $value): string => is_array($value) ? implode(',', $value) : (string) $value);
        $queryBuilder->method('executeQuery')->willReturn($result);

        // The page-column query runs first, the container-column query (if any) second.
        $result->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            $pageRows,
            $containerRows ?? [],
        );

        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
    }
}
