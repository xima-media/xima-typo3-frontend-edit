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
    protected function tearDown(): void
    {
        unset($GLOBALS['TCA']);
    }

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

    #[Test]
    public function getIconIdentifierForRecordResolvesViaTypeiconColumn(): void
    {
        $GLOBALS['TCA']['tx_news_domain_model_news']['ctrl']['typeicon_column'] = 'CType';
        $GLOBALS['TCA']['tx_news_domain_model_news']['ctrl']['typeicon_classes'] = [
            'default' => 'content-news',
            'foo' => 'content-news-foo',
        ];

        $service = new IconService($this->createMock(IconFactory::class));

        self::assertSame(
            'content-news-foo',
            $service->getIconIdentifierForRecord('tx_news_domain_model_news', ['CType' => 'foo']),
        );
    }

    #[Test]
    public function getIconIdentifierForRecordFallsBackToDefaultWhenColumnValueUnknown(): void
    {
        $GLOBALS['TCA']['tx_news_domain_model_news']['ctrl']['typeicon_column'] = 'CType';
        $GLOBALS['TCA']['tx_news_domain_model_news']['ctrl']['typeicon_classes'] = [
            'default' => 'content-news',
        ];

        $service = new IconService($this->createMock(IconFactory::class));

        self::assertSame(
            'content-news',
            $service->getIconIdentifierForRecord('tx_news_domain_model_news', ['CType' => 'unknown']),
        );
    }

    #[Test]
    public function getIconIdentifierForRecordUsesDefaultWhenNoTypeiconColumnConfigured(): void
    {
        $GLOBALS['TCA']['sys_category']['ctrl']['typeicon_classes'] = [
            'default' => 'mimetypes-x-sys_category',
        ];

        $service = new IconService($this->createMock(IconFactory::class));

        self::assertSame(
            'mimetypes-x-sys_category',
            $service->getIconIdentifierForRecord('sys_category', ['uid' => 1]),
        );
    }

    #[Test]
    public function getIconIdentifierForRecordFallsBackToGenericIdentifierWhenTableHasNoIconConfig(): void
    {
        $service = new IconService($this->createMock(IconFactory::class));

        self::assertSame(
            'mimetypes-other-other',
            $service->getIconIdentifierForRecord('tx_unknown_extension_table', ['uid' => 1]),
        );
    }
}
