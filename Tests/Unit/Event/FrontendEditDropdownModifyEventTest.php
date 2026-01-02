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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * FrontendEditDropdownModifyEventTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(FrontendEditDropdownModifyEvent::class)]
final class FrontendEditDropdownModifyEventTest extends TestCase
{
    #[Test]
    public function eventNameConstantIsCorrect(): void
    {
        self::assertSame(
            'xima_typo3_frontend_edit.frontend_edit.dropdown.modify',
            FrontendEditDropdownModifyEvent::NAME,
        );
    }

    #[Test]
    public function constructorSetsPropertiesCorrectly(): void
    {
        $contentElement = [
            'uid' => 123,
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Test Header',
        ];
        $menuButton = new Button('Menu', ButtonType::Menu);
        $returnUrl = 'https://example.com/return';

        $event = new FrontendEditDropdownModifyEvent($contentElement, $menuButton, $returnUrl);

        self::assertSame($contentElement, $event->getContentElement());
        self::assertSame($menuButton, $event->getMenuButton());
        self::assertSame($returnUrl, $event->getReturnUrl());
    }

    #[Test]
    public function getContentElementReturnsContentElementArray(): void
    {
        $contentElement = [
            'uid' => 456,
            'pid' => 2,
            'CType' => 'textmedia',
            'bodytext' => 'Some content',
        ];
        $menuButton = new Button('Menu', ButtonType::Menu);

        $event = new FrontendEditDropdownModifyEvent($contentElement, $menuButton, '/return');

        self::assertSame(456, $event->getContentElement()['uid']);
        self::assertSame('textmedia', $event->getContentElement()['CType']);
    }

    #[Test]
    public function setMenuButtonReplacesMenuButton(): void
    {
        $contentElement = ['uid' => 1];
        $originalButton = new Button('Original', ButtonType::Menu);
        $newButton = new Button('New', ButtonType::Menu);

        $event = new FrontendEditDropdownModifyEvent($contentElement, $originalButton, '/return');
        $event->setMenuButton($newButton);

        self::assertSame($newButton, $event->getMenuButton());
        self::assertSame('New', $event->getMenuButton()->getLabel());
    }

    #[Test]
    public function getReturnUrlReturnsReturnUrl(): void
    {
        $contentElement = ['uid' => 1];
        $menuButton = new Button('Menu', ButtonType::Menu);
        $returnUrl = '/typo3/module/page?id=1';

        $event = new FrontendEditDropdownModifyEvent($contentElement, $menuButton, $returnUrl);

        self::assertSame($returnUrl, $event->getReturnUrl());
    }

    #[Test]
    public function eventWorksWithEmptyContentElement(): void
    {
        $contentElement = [];
        $menuButton = new Button('Menu', ButtonType::Menu);

        $event = new FrontendEditDropdownModifyEvent($contentElement, $menuButton, '/return');

        self::assertSame([], $event->getContentElement());
    }

    #[Test]
    public function eventWorksWithEmptyReturnUrl(): void
    {
        $contentElement = ['uid' => 1];
        $menuButton = new Button('Menu', ButtonType::Menu);

        $event = new FrontendEditDropdownModifyEvent($contentElement, $menuButton, '');

        self::assertSame('', $event->getReturnUrl());
    }
}
