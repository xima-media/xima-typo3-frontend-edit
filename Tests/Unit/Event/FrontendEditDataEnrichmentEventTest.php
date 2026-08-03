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

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use stdClass;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDataEnrichmentEvent;

/**
 * FrontendEditDataEnrichmentEventTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(FrontendEditDataEnrichmentEvent::class)]
final class FrontendEditDataEnrichmentEventTest extends TestCase
{
    #[Test]
    public function eventNameConstantIsCorrect(): void
    {
        self::assertSame(
            'xima_typo3_frontend_edit.frontend_edit.data.enrichment',
            FrontendEditDataEnrichmentEvent::NAME,
        );
    }

    #[Test]
    public function constructorSetsPropertiesCorrectly(): void
    {
        $contentElements = [1 => ['uid' => 1, 'CType' => 'text']];
        $event = new FrontendEditDataEnrichmentEvent($contentElements, 5, 0, '/return');

        self::assertSame($contentElements, $event->getContentElements());
        self::assertSame(5, $event->getPageId());
        self::assertSame(0, $event->getLanguageUid());
        self::assertSame('/return', $event->getReturnUrl());
    }

    #[Test]
    public function getContentElementUidsReturnsAllUidsAsIntegers(): void
    {
        $contentElements = [1 => ['uid' => 1], 2 => ['uid' => 2], 42 => ['uid' => 42]];
        $event = new FrontendEditDataEnrichmentEvent($contentElements, 5, 0, '/return');

        self::assertSame([1, 2, 42], $event->getContentElementUids());
    }

    #[Test]
    public function getElementDataReturnsEmptyArrayWhenNothingAttached(): void
    {
        $event = new FrontendEditDataEnrichmentEvent([1 => ['uid' => 1]], 5, 0, '/return');

        self::assertSame([], $event->getElementData(1));
    }

    #[Test]
    public function addElementDataIsRetrievableUnderItsNamespace(): void
    {
        $event = new FrontendEditDataEnrichmentEvent([1 => ['uid' => 1]], 5, 0, '/return');

        $event->addElementData(1, 'my_ext', ['color' => '#ff0000', 'count' => 3]);

        self::assertSame(
            ['my_ext' => ['color' => '#ff0000', 'count' => 3]],
            $event->getElementData(1),
        );
    }

    #[Test]
    public function addElementDataKeepsNamespacesSeparate(): void
    {
        $event = new FrontendEditDataEnrichmentEvent([1 => ['uid' => 1]], 5, 0, '/return');

        $event->addElementData(1, 'ext_one', ['a' => 1]);
        $event->addElementData(1, 'ext_two', ['b' => 2]);

        self::assertSame(
            ['ext_one' => ['a' => 1], 'ext_two' => ['b' => 2]],
            $event->getElementData(1),
        );
    }

    #[Test]
    public function addElementDataAcceptsNestedArraysAndScalarTypes(): void
    {
        $event = new FrontendEditDataEnrichmentEvent([1 => ['uid' => 1]], 5, 0, '/return');

        $event->addElementData(1, 'ext', [
            'string' => 'a',
            'int' => 1,
            'float' => 1.5,
            'bool' => true,
            'null' => null,
            'nested' => ['deep' => ['value' => 1]],
        ]);

        self::assertSame(1, $event->getElementData(1)['ext']['nested']['deep']['value']);
    }

    #[Test]
    public function addElementDataRejectsUnknownUid(): void
    {
        $event = new FrontendEditDataEnrichmentEvent([1 => ['uid' => 1]], 5, 0, '/return');

        $this->expectException(InvalidArgumentException::class);
        $event->addElementData(999, 'ext', ['a' => 1]);
    }

    #[Test]
    public function addElementDataRejectsInvalidNamespace(): void
    {
        $event = new FrontendEditDataEnrichmentEvent([1 => ['uid' => 1]], 5, 0, '/return');

        $this->expectException(InvalidArgumentException::class);
        $event->addElementData(1, '_ext', ['a' => 1]);
    }

    #[Test]
    public function addElementDataRejectsNonSerializableValues(): void
    {
        $event = new FrontendEditDataEnrichmentEvent([1 => ['uid' => 1]], 5, 0, '/return');

        $this->expectException(InvalidArgumentException::class);
        $event->addElementData(1, 'ext', ['object' => new stdClass()]);
    }

    #[Test]
    public function addElementDataRejectsNonSerializableNestedValues(): void
    {
        $event = new FrontendEditDataEnrichmentEvent([1 => ['uid' => 1]], 5, 0, '/return');

        $this->expectException(InvalidArgumentException::class);
        $event->addElementData(1, 'ext', ['nested' => ['object' => new stdClass()]]);
    }
}
