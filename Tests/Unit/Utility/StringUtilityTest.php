<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "xima_typo3_frontend_edit".
 *
 * Copyright (C) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use Xima\XimaTypo3FrontendEdit\Utility\StringUtility;

class StringUtilityTest extends TestCase
{
    public function testShortenStringReturnsOriginalStringWhenShorterThanMaxLength(): void
    {
        $input = 'Short string';
        $result = StringUtility::shortenString($input, 30);

        self::assertEquals($input, $result);
    }

    public function testShortenStringReturnsOriginalStringWhenEqualToMaxLength(): void
    {
        $input = str_repeat('a', 30);
        $result = StringUtility::shortenString($input, 30);

        self::assertEquals($input, $result);
    }

    public function testShortenStringTruncatesLongStringWithEllipsis(): void
    {
        $input = 'This is a very long string that should be truncated';
        $result = StringUtility::shortenString($input, 20);

        self::assertEquals('This is a very long …', $result);
        self::assertEquals(21, mb_strlen($result)); // 20 chars + ellipsis
    }

    public function testShortenStringUsesDefaultMaxLengthOf30(): void
    {
        $input = str_repeat('a', 50);
        $result = StringUtility::shortenString($input);

        self::assertEquals(str_repeat('a', 30) . '…', $result);
        self::assertEquals(31, mb_strlen($result)); // 30 chars + ellipsis
    }

    public function testShortenStringHandlesMultibyteCharacters(): void
    {
        $input = 'Ä string with ümlaut characters and émojis 😀';
        $result = StringUtility::shortenString($input, 20);

        self::assertEquals('Ä string with ümlaut…', $result);
        self::assertEquals(21, mb_strlen($result));
    }

    public function testShortenStringHandlesEmptyString(): void
    {
        $result = StringUtility::shortenString('', 10);

        self::assertEquals('', $result);
    }

    public function testShortenStringHandlesMaxLengthOfZero(): void
    {
        $input = 'Any string';
        $result = StringUtility::shortenString($input, 0);

        self::assertEquals('…', $result);
    }

    public function testShortenStringHandlesMaxLengthOfOne(): void
    {
        $input = 'Any string';
        $result = StringUtility::shortenString($input, 1);

        self::assertEquals('A…', $result);
    }

    public function testStringUtilityMethodIsStatic(): void
    {
        $reflection = new \ReflectionClass(StringUtility::class);
        $method = $reflection->getMethod('shortenString');

        self::assertTrue($method->isStatic());
        self::assertTrue($method->isPublic());
    }

    public function testStringUtilityMethodParameters(): void
    {
        $reflection = new \ReflectionClass(StringUtility::class);
        $method = $reflection->getMethod('shortenString');
        $parameters = $method->getParameters();

        self::assertCount(2, $parameters);

        // First parameter: string
        $firstParamType = $parameters[0]->getType();
        self::assertEquals('string', $parameters[0]->getName());
        self::assertTrue($parameters[0]->hasType());
        self::assertInstanceOf(\ReflectionNamedType::class, $firstParamType);
        self::assertEquals('string', $firstParamType->getName());

        // Second parameter: maxLength with default value 30
        $secondParamType = $parameters[1]->getType();
        self::assertEquals('maxLength', $parameters[1]->getName());
        self::assertTrue($parameters[1]->hasType());
        self::assertInstanceOf(\ReflectionNamedType::class, $secondParamType);
        self::assertEquals('int', $secondParamType->getName());
        self::assertTrue($parameters[1]->isDefaultValueAvailable());
        self::assertEquals(30, $parameters[1]->getDefaultValue());
    }
}
