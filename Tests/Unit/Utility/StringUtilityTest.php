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

use PHPUnit\Framework\Attributes\{DataProvider, Test};
use PHPUnit\Framework\TestCase;
use Xima\XimaTypo3FrontendEdit\Utility\StringUtility;

/**
 * StringUtilityTest.
 *
 * @covers \Xima\XimaTypo3FrontendEdit\Utility\StringUtility
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class StringUtilityTest extends TestCase
{
    /**
     * @return array<string, array{input: string, maxLength: int, expected: string}>
     */
    public static function shortenStringDataProvider(): array
    {
        return [
            'string shorter than max length' => [
                'input' => 'Hello',
                'maxLength' => 30,
                'expected' => 'Hello',
            ],
            'string equal to max length' => [
                'input' => 'Hello World',
                'maxLength' => 11,
                'expected' => 'Hello World',
            ],
            'string longer than max length' => [
                'input' => 'This is a very long string that needs to be shortened',
                'maxLength' => 20,
                'expected' => 'This is a very long …',
            ],
            'empty string' => [
                'input' => '',
                'maxLength' => 30,
                'expected' => '',
            ],
            'single character below max' => [
                'input' => 'A',
                'maxLength' => 30,
                'expected' => 'A',
            ],
            'string with unicode characters' => [
                'input' => 'Hällo Wörld mit Ümlauten',
                'maxLength' => 10,
                'expected' => 'Hällo Wörl…',
            ],
            'string with emoji' => [
                'input' => 'Hello 🌍 World',
                'maxLength' => 8,
                'expected' => 'Hello 🌍 …',
            ],
            'max length of 1 with longer string' => [
                'input' => 'Hello',
                'maxLength' => 1,
                'expected' => 'H…',
            ],
            'default max length (30) with long string' => [
                'input' => 'This string has exactly thirty-five characters!',
                'maxLength' => 30,
                'expected' => 'This string has exactly thirty…',
            ],
        ];
    }

    #[Test]
    #[DataProvider('shortenStringDataProvider')]
    public function shortenStringReturnsExpectedResult(string $input, int $maxLength, string $expected): void
    {
        $result = StringUtility::shortenString($input, $maxLength);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function shortenStringUsesDefaultMaxLength(): void
    {
        $shortString = 'Short';
        $longString = 'This is a string that is longer than thirty characters';

        self::assertSame($shortString, StringUtility::shortenString($shortString));
        self::assertSame('This is a string that is longe…', StringUtility::shortenString($longString));
    }
}
