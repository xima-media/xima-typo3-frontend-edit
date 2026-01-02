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
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditPageDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * FrontendEditPageDropdownModifyEventTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(FrontendEditPageDropdownModifyEvent::class)]
final class FrontendEditPageDropdownModifyEventTest extends TestCase
{
    #[Test]
    public function eventNameConstantIsCorrect(): void
    {
        self::assertSame(
            'xima_typo3_frontend_edit.frontend_edit.page_dropdown.modify',
            FrontendEditPageDropdownModifyEvent::NAME,
        );
    }

    #[Test]
    public function constructorSetsPropertiesCorrectly(): void
    {
        $pageId = 42;
        $languageUid = 1;
        $menuButton = new Button('Page Menu', ButtonType::Menu);
        $returnUrl = 'https://example.com/return';

        $event = new FrontendEditPageDropdownModifyEvent($pageId, $languageUid, $menuButton, $returnUrl);

        self::assertSame($pageId, $event->getPageId());
        self::assertSame($languageUid, $event->getLanguageUid());
        self::assertSame($menuButton, $event->getMenuButton());
        self::assertSame($returnUrl, $event->getReturnUrl());
    }

    #[Test]
    public function getPageIdReturnsPageId(): void
    {
        $menuButton = new Button('Menu', ButtonType::Menu);
        $event = new FrontendEditPageDropdownModifyEvent(123, 0, $menuButton, '/return');

        self::assertSame(123, $event->getPageId());
    }

    #[Test]
    public function getLanguageUidReturnsLanguageUid(): void
    {
        $menuButton = new Button('Menu', ButtonType::Menu);
        $event = new FrontendEditPageDropdownModifyEvent(1, 2, $menuButton, '/return');

        self::assertSame(2, $event->getLanguageUid());
    }

    #[Test]
    public function setMenuButtonReplacesMenuButton(): void
    {
        $originalButton = new Button('Original', ButtonType::Menu);
        $newButton = new Button('New', ButtonType::Menu);

        $event = new FrontendEditPageDropdownModifyEvent(1, 0, $originalButton, '/return');
        $event->setMenuButton($newButton);

        self::assertSame($newButton, $event->getMenuButton());
        self::assertSame('New', $event->getMenuButton()->getLabel());
    }

    #[Test]
    public function getReturnUrlReturnsReturnUrl(): void
    {
        $menuButton = new Button('Menu', ButtonType::Menu);
        $returnUrl = '/typo3/module/page?id=1';

        $event = new FrontendEditPageDropdownModifyEvent(1, 0, $menuButton, $returnUrl);

        self::assertSame($returnUrl, $event->getReturnUrl());
    }

    #[Test]
    public function eventWorksWithZeroPageId(): void
    {
        $menuButton = new Button('Menu', ButtonType::Menu);
        $event = new FrontendEditPageDropdownModifyEvent(0, 0, $menuButton, '/return');

        self::assertSame(0, $event->getPageId());
    }

    #[Test]
    public function eventWorksWithDefaultLanguage(): void
    {
        $menuButton = new Button('Menu', ButtonType::Menu);
        $event = new FrontendEditPageDropdownModifyEvent(1, 0, $menuButton, '/return');

        self::assertSame(0, $event->getLanguageUid());
    }

    #[Test]
    public function eventWorksWithEmptyReturnUrl(): void
    {
        $menuButton = new Button('Menu', ButtonType::Menu);
        $event = new FrontendEditPageDropdownModifyEvent(1, 0, $menuButton, '');

        self::assertSame('', $event->getReturnUrl());
    }
}
