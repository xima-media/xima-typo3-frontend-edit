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

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
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
        $subject = new ContainerContextResolver();

        $result = $subject->resolve(null, 0, ['uid' => 1, 'pid' => 1, 'CType' => 'text']);

        self::assertTrue($result['valid']);
        self::assertSame(0, $result['colPos']);
        self::assertSame(0, $result['containerUid']);
        self::assertNull($result['error']);
    }
}
