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
    public function resolveRejectsColumnNotRegisteredForAnyContainer(): void
    {
        $this->registerContainer('test_container', [201, 202]);

        $result = $this->createSubject()->resolve(3, 999, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('column 999', (string) $result['error']);
    }

    #[Test]
    public function resolveRejectsMovingAContainerIntoAContainer(): void
    {
        $this->registerContainer('test_container', [201, 202]);

        $result = $this->createSubject()->resolve(3, 201, ['uid' => 9, 'pid' => 1, 'CType' => 'test_container']);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('container element', (string) $result['error']);
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

    private function createSubject(): ContainerContextResolver
    {
        return new ContainerContextResolver(
            new Registry($this->createMock(EventDispatcherInterface::class)),
        );
    }
}
