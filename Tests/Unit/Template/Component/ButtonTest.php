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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Template\Component;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * ButtonTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(Button::class)]
final class ButtonTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['LANG']);
    }

    #[Test]
    public function constructorSetsPropertiesCorrectly(): void
    {
        $button = new Button('Test Label', ButtonType::Link, 'https://example.com', null, true);

        self::assertSame('Test Label', $button->getLabel());
        self::assertSame(ButtonType::Link, $button->getType());
        self::assertSame('https://example.com', $button->getUrl());
        self::assertNull($button->getIcon());
        self::assertTrue($button->isTargetBlank());
        self::assertSame([], $button->getChildren());
    }

    #[Test]
    public function constructorSetsDefaultValues(): void
    {
        $button = new Button('Label', ButtonType::Menu);

        self::assertNull($button->getUrl());
        self::assertNull($button->getIcon());
        self::assertFalse($button->isTargetBlank());
    }

    #[Test]
    public function setLabelChangesLabel(): void
    {
        $button = new Button('Original', ButtonType::Link);
        $button->setLabel('Changed');

        self::assertSame('Changed', $button->getLabel());
    }

    #[Test]
    public function setTypeChangesType(): void
    {
        $button = new Button('Label', ButtonType::Link);
        $button->setType(ButtonType::Divider);

        self::assertSame(ButtonType::Divider, $button->getType());
    }

    #[Test]
    public function setUrlChangesUrl(): void
    {
        $button = new Button('Label', ButtonType::Link);
        $button->setUrl('https://new-url.com');

        self::assertSame('https://new-url.com', $button->getUrl());
    }

    #[Test]
    public function setIconChangesIcon(): void
    {
        $button = new Button('Label', ButtonType::Link);
        $icon = $this->createStub(Icon::class);
        $button->setIcon($icon);

        self::assertSame($icon, $button->getIcon());
    }

    #[Test]
    public function setTargetBlankChangesTargetBlank(): void
    {
        $button = new Button('Label', ButtonType::Link);
        $button->setTargetBlank(true);

        self::assertTrue($button->isTargetBlank());
    }

    #[Test]
    public function appendChildAddsChildWithKey(): void
    {
        $button = new Button('Parent', ButtonType::Menu);
        $child = new Button('Child', ButtonType::Link);

        $button->appendChild($child, 'child1');

        $children = $button->getChildren();
        self::assertCount(1, $children);
        self::assertArrayHasKey('child1', $children);
        self::assertSame($child, $children['child1']);
    }

    #[Test]
    public function appendChildWithIntegerKey(): void
    {
        $button = new Button('Parent', ButtonType::Menu);
        $child = new Button('Child', ButtonType::Link);

        $button->appendChild($child, 0);

        $children = $button->getChildren();
        self::assertArrayHasKey(0, $children);
    }

    #[Test]
    public function setChildrenReplacesAllChildren(): void
    {
        $button = new Button('Parent', ButtonType::Menu);
        $child1 = new Button('Child 1', ButtonType::Link);
        $child2 = new Button('Child 2', ButtonType::Link);

        $button->appendChild($child1, 'old');
        $button->setChildren(['new1' => $child2]);

        $children = $button->getChildren();
        self::assertCount(1, $children);
        self::assertArrayNotHasKey('old', $children);
        self::assertArrayHasKey('new1', $children);
    }

    #[Test]
    public function appendAfterChildInsertsAfterExistingChild(): void
    {
        $button = new Button('Parent', ButtonType::Menu);
        $child1 = new Button('First', ButtonType::Link);
        $child2 = new Button('Second', ButtonType::Link);
        $child3 = new Button('Third', ButtonType::Link);
        $newChild = new Button('New', ButtonType::Link);

        $button->appendChild($child1, 'first');
        $button->appendChild($child2, 'second');
        $button->appendChild($child3, 'third');

        $button->appendAfterChild($newChild, 'first', 'new');

        $children = $button->getChildren();
        $keys = array_keys($children);

        self::assertSame(['first', 'new', 'second', 'third'], $keys);
        self::assertSame($newChild, $children['new']);
    }

    #[Test]
    public function appendAfterChildAppendsAtEndWhenKeyNotFound(): void
    {
        $button = new Button('Parent', ButtonType::Menu);
        $child1 = new Button('First', ButtonType::Link);
        $newChild = new Button('New', ButtonType::Link);

        $button->appendChild($child1, 'first');
        $button->appendAfterChild($newChild, 'nonexistent', 'new');

        $children = $button->getChildren();
        self::assertCount(2, $children);
        self::assertArrayHasKey('new', $children);
    }

    #[Test]
    public function appendAfterChildInsertsAfterLastChild(): void
    {
        $button = new Button('Parent', ButtonType::Menu);
        $child1 = new Button('First', ButtonType::Link);
        $child2 = new Button('Last', ButtonType::Link);
        $newChild = new Button('New', ButtonType::Link);

        $button->appendChild($child1, 'first');
        $button->appendChild($child2, 'last');

        $button->appendAfterChild($newChild, 'last', 'new');

        $keys = array_keys($button->getChildren());
        self::assertSame(['first', 'last', 'new'], $keys);
    }

    #[Test]
    public function removeChildRemovesExistingChild(): void
    {
        $button = new Button('Parent', ButtonType::Menu);
        $child1 = new Button('First', ButtonType::Link);
        $child2 = new Button('Second', ButtonType::Link);

        $button->appendChild($child1, 'first');
        $button->appendChild($child2, 'second');

        $button->removeChild('first');

        $children = $button->getChildren();
        self::assertCount(1, $children);
        self::assertArrayNotHasKey('first', $children);
        self::assertArrayHasKey('second', $children);
    }

    #[Test]
    public function removeChildDoesNothingForNonexistentKey(): void
    {
        $button = new Button('Parent', ButtonType::Menu);
        $child = new Button('Child', ButtonType::Link);

        $button->appendChild($child, 'child');
        $button->removeChild('nonexistent');

        self::assertCount(1, $button->getChildren());
    }

    #[Test]
    public function renderReturnsBasicStructure(): void
    {
        $this->setupLanguageServiceStub();

        $button = new Button('LLL:EXT:test/label', ButtonType::Link);
        $result = $button->render();

        self::assertArrayHasKey('label', $result);
        self::assertArrayHasKey('type', $result);
        self::assertSame('link', $result['type']);
    }

    #[Test]
    public function renderIncludesUrlAndTargetBlankWhenUrlIsSet(): void
    {
        $this->setupLanguageServiceStub();

        $button = new Button('Label', ButtonType::Link, 'https://example.com', null, true);
        $result = $button->render();

        self::assertArrayHasKey('url', $result);
        self::assertSame('https://example.com', $result['url']);
        self::assertArrayHasKey('targetBlank', $result);
        self::assertTrue($result['targetBlank']);
    }

    #[Test]
    public function renderExcludesUrlWhenEmpty(): void
    {
        $this->setupLanguageServiceStub();

        $button = new Button('Label', ButtonType::Link, '');
        $result = $button->render();

        self::assertArrayNotHasKey('url', $result);
    }

    #[Test]
    public function renderExcludesUrlWhenNull(): void
    {
        $this->setupLanguageServiceStub();

        $button = new Button('Label', ButtonType::Link);
        $result = $button->render();

        self::assertArrayNotHasKey('url', $result);
    }

    #[Test]
    public function renderIncludesIconMarkupWhenIconIsSet(): void
    {
        $this->setupLanguageServiceStub();

        $icon = $this->createStub(Icon::class);
        $icon->method('getAlternativeMarkup')->willReturn('<svg>icon</svg>');

        $button = new Button('Label', ButtonType::Link, null, $icon);
        $result = $button->render();

        self::assertArrayHasKey('icon', $result);
        self::assertSame('<svg>icon</svg>', $result['icon']);
    }

    #[Test]
    public function renderExcludesIconWhenNull(): void
    {
        $this->setupLanguageServiceStub();

        $button = new Button('Label', ButtonType::Link);
        $result = $button->render();

        self::assertArrayNotHasKey('icon', $result);
    }

    #[Test]
    public function renderIncludesChildrenWhenPresent(): void
    {
        $this->setupLanguageServiceStub();

        $parent = new Button('Parent', ButtonType::Menu);
        $child = new Button('Child', ButtonType::Link, 'https://child.com');

        $parent->appendChild($child, 'child1');
        $result = $parent->render();

        self::assertArrayHasKey('children', $result);
        self::assertIsArray($result['children']);
        self::assertArrayHasKey('child1', $result['children']);
        self::assertSame('Child', $result['children']['child1']['label']);
    }

    #[Test]
    public function renderExcludesChildrenWhenEmpty(): void
    {
        $this->setupLanguageServiceStub();

        $button = new Button('Label', ButtonType::Menu);
        $result = $button->render();

        self::assertArrayNotHasKey('children', $result);
    }

    private function setupLanguageServiceStub(): void
    {
        $languageService = $this->createStub(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(static fn (string $key): string => $key);
        $GLOBALS['LANG'] = $languageService;
    }
}
