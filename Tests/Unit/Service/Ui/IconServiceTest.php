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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Service\Ui;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory, IconSize};
use Xima\XimaTypo3FrontendEdit\Service\Ui\IconService;

/**
 * IconServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(IconService::class)]
final class IconServiceTest extends TestCase
{
    #[Test]
    public function getIconReturnsIconFromFactory(): void
    {
        $icon = $this->createMock(Icon::class);
        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->expects(self::once())
            ->method('getIcon')
            ->with('actions-open', IconSize::SMALL)
            ->willReturn($icon);

        $service = new IconService($iconFactory);

        self::assertSame($icon, $service->getIcon('actions-open'));
    }

    #[Test]
    public function getIconCachesResultAndCallsFactoryOnce(): void
    {
        $icon = $this->createMock(Icon::class);
        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->expects(self::once())
            ->method('getIcon')
            ->willReturn($icon);

        $service = new IconService($iconFactory);

        $first = $service->getIcon('actions-open');
        $second = $service->getIcon('actions-open');

        self::assertSame($first, $second);
    }

    #[Test]
    public function getIconRequestsSeparateIconsForDifferentIdentifiers(): void
    {
        $iconOpen = $this->createMock(Icon::class);
        $iconClose = $this->createMock(Icon::class);
        $iconFactory = $this->createMock(IconFactory::class);
        $iconFactory->expects(self::exactly(2))
            ->method('getIcon')
            ->willReturnMap([
                ['actions-open', IconSize::SMALL, null, null, $iconOpen],
                ['actions-close', IconSize::SMALL, null, null, $iconClose],
            ]);

        $service = new IconService($iconFactory);

        self::assertSame($iconOpen, $service->getIcon('actions-open'));
        self::assertSame($iconClose, $service->getIcon('actions-close'));
    }
}
