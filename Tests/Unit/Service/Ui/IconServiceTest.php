<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Ui;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory, IconSize};
use Xima\XimaTypo3FrontendEdit\Service\Ui\IconService;

/**
 * IconServiceTest.
 *
 * @covers \Xima\XimaTypo3FrontendEdit\Service\Ui\IconService
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class IconServiceTest extends TestCase
{
    #[Test]
    public function getIconReturnsIconFromFactory(): void
    {
        $icon = $this->createStub(Icon::class);

        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->expects(self::once())
            ->method('getIcon')
            ->with('test-icon', IconSize::SMALL)
            ->willReturn($icon);

        $iconService = new IconService($iconFactory);

        $result = $iconService->getIcon('test-icon');

        self::assertSame($icon, $result);
    }

    #[Test]
    public function getIconCachesIconByIdentifier(): void
    {
        $icon = $this->createStub(Icon::class);

        $iconFactory = $this->createMock(IconFactory::class);
        // Factory should only be called once due to caching
        $iconFactory->expects(self::once())
            ->method('getIcon')
            ->with('cached-icon', IconSize::SMALL)
            ->willReturn($icon);

        $iconService = new IconService($iconFactory);

        // First call - should hit the factory
        $result1 = $iconService->getIcon('cached-icon');
        // Second call - should use cache
        $result2 = $iconService->getIcon('cached-icon');

        self::assertSame($icon, $result1);
        self::assertSame($icon, $result2);
        self::assertSame($result1, $result2);
    }

    #[Test]
    public function getIconCachesDifferentIconsSeparately(): void
    {
        $icon1 = $this->createStub(Icon::class);
        $icon2 = $this->createStub(Icon::class);

        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->expects(self::exactly(2))
            ->method('getIcon')
            ->willReturnCallback(fn (string $identifier): Icon => 'icon-one' === $identifier ? $icon1 : $icon2);

        $iconService = new IconService($iconFactory);

        $result1 = $iconService->getIcon('icon-one');
        $result2 = $iconService->getIcon('icon-two');

        self::assertSame($icon1, $result1);
        self::assertSame($icon2, $result2);
        self::assertNotSame($result1, $result2);
    }
}
