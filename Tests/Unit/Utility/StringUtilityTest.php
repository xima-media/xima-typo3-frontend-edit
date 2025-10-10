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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Utility;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Xima\XimaTypo3FrontendEdit\Utility\StringUtility;

/**
 * StringUtilityTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
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

        self::assertEquals(str_repeat('a', 30).'…', $result);
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
        $reflection = new ReflectionClass(StringUtility::class);
        $method = $reflection->getMethod('shortenString');

        self::assertTrue($method->isStatic());
        self::assertTrue($method->isPublic());
    }

    public function testStringUtilityMethodParameters(): void
    {
        $reflection = new ReflectionClass(StringUtility::class);
        $method = $reflection->getMethod('shortenString');
        $parameters = $method->getParameters();

        self::assertCount(2, $parameters);

        // First parameter: string
        $firstParamType = $parameters[0]->getType();
        self::assertEquals('string', $parameters[0]->getName());
        self::assertTrue($parameters[0]->hasType());
        self::assertInstanceOf(ReflectionNamedType::class, $firstParamType);
        self::assertEquals('string', $firstParamType->getName());

        // Second parameter: maxLength with default value 30
        $secondParamType = $parameters[1]->getType();
        self::assertEquals('maxLength', $parameters[1]->getName());
        self::assertTrue($parameters[1]->hasType());
        self::assertInstanceOf(ReflectionNamedType::class, $secondParamType);
        self::assertEquals('int', $secondParamType->getName());
        self::assertTrue($parameters[1]->isDefaultValueAvailable());
        self::assertEquals(30, $parameters[1]->getDefaultValue());
    }
}
