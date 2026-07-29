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

use B13\Container\Tca\Registry;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Xima\XimaTypo3FrontendEdit\Service\Content\ContainerContextResolver;

/**
 * ContainerContextResolverTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ContainerContextResolver::class)]
final class ContainerContextResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TCA']);
    }

    #[Test]
    public function resolveAcceptsPageColumnWhenNoContainerGiven(): void
    {
        $result = $this->createSubject()->resolve(null, 0, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertTrue($result['valid']);
        self::assertSame(0, $result['colPos']);
        self::assertSame(0, $result['containerUid']);
        self::assertNull($result['error']);
    }

    #[Test]
    public function resolveRejectsMovingAContainerIntoAContainer(): void
    {
        $this->registerContainer('test_container', [201, 202]);

        $result = $this->createSubject()->resolve(3, 201, ['uid' => 9, 'pid' => 1, 'CType' => 'test_container']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('container element', (string) $result['error']);
    }

    #[Test]
    public function resolveRejectsMissingContainer(): void
    {
        $this->registerContainer('test_container', [201]);

        $result = $this->createSubject([])->resolve(3, 201, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('not found', (string) $result['error']);
    }

    #[Test]
    public function resolveRejectsContainerOnAnotherPage(): void
    {
        $this->registerContainer('test_container', [201]);

        $result = $this->createSubject([
            ['uid' => 3, 'pid' => 99, 'CType' => 'test_container'],
        ])->resolve(3, 201, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('different page', (string) $result['error']);
    }

    #[Test]
    public function resolveRejectsTargetThatIsNotAContainerElement(): void
    {
        $this->registerContainer('test_container', [201]);

        $result = $this->createSubject([
            ['uid' => 3, 'pid' => 1, 'CType' => 'text'],
        ])->resolve(3, 201, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('not a container', (string) $result['error']);
    }

    #[Test]
    public function resolveRejectsColumnNotBelongingToThisContainer(): void
    {
        $this->registerContainer('test_container', [201]);
        $this->registerContainer('other_container', [301]);

        // 301 is a registered container column, but not one of test_container's.
        $result = $this->createSubject([
            ['uid' => 3, 'pid' => 1, 'CType' => 'test_container'],
        ])->resolve(3, 301, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('column 301', (string) $result['error']);
    }

    #[Test]
    public function resolveRejectsDisallowedContentType(): void
    {
        $this->registerContainer('test_container', [201]);
        $GLOBALS['TCA']['tt_content']['containerConfiguration']['test_container']['grid'][0][0]['allowedContentTypes'] = 'image';

        $result = $this->createSubject([
            ['uid' => 3, 'pid' => 1, 'CType' => 'test_container'],
        ])->resolve(3, 201, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('not allowed', (string) $result['error']);
    }

    #[Test]
    public function resolveRejectsContainerColumnWithoutContainerUid(): void
    {
        $this->registerContainer('test_container', [201]);

        // Rule 6 — the orphan path: colPos 201 with no container would leave a
        // container column number without a container binding.
        $result = $this->createSubject()->resolve(null, 201, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('no container was given', (string) $result['error']);
    }

    #[Test]
    public function resolveAcceptsValidContainerColumn(): void
    {
        $this->registerContainer('test_container', [201]);

        $result = $this->createSubject([
            ['uid' => 3, 'pid' => 1, 'CType' => 'test_container'],
        ])->resolve(3, 201, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertTrue($result['valid']);
        self::assertSame(201, $result['colPos']);
        self::assertSame(3, $result['containerUid']);
    }

    /**
     * @param int[] $colPositions
     */
    private function registerContainer(string $cType, array $colPositions): void
    {
        $columns = [];
        foreach ($colPositions as $colPos) {
            $columns[] = ['name' => 'Column '.$colPos, 'colPos' => $colPos];
        }

        // Mirrors the shape b13/container writes via Tca\Registry::configureContainer().
        $GLOBALS['TCA']['tt_content']['containerConfiguration'][$cType] = [
            'cType' => $cType,
            'grid' => [$columns],
        ];
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    private function createSubject(array $records = []): ContainerContextResolver
    {
        return new ContainerContextResolver(
            new Registry($this->createMock(EventDispatcherInterface::class)),
            $this->createConnectionPoolReturning($records),
        );
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    private function createConnectionPoolReturning(array $records): ConnectionPool
    {
        $result = $this->createMock(\Doctrine\DBAL\Result::class);
        $result->method('fetchAssociative')->willReturn($records[0] ?? false);

        $expressionBuilder = $this->createMock(\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder::class);
        $expressionBuilder->method('eq')->willReturn('');

        $restrictions = $this->createMock(\TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer::class);

        $queryBuilder = $this->createMock(\TYPO3\CMS\Core\Database\Query\QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('getRestrictions')->willReturn($restrictions);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('createNamedParameter')->willReturn('?');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        return $connectionPool;
    }
}
