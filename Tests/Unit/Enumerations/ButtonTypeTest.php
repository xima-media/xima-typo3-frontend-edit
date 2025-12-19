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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Enumerations;

use PHPUnit\Framework\Attributes\{DataProvider, Test};
use PHPUnit\Framework\TestCase;
use ValueError;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;

/**
 * ButtonTypeTest.
 *
 * @covers \Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ButtonTypeTest extends TestCase
{
    #[Test]
    public function enumHasExpectedCases(): void
    {
        $cases = ButtonType::cases();

        self::assertCount(4, $cases);
    }

    /**
     * @return array<string, array{case: ButtonType, expectedValue: string}>
     */
    public static function buttonTypeCasesDataProvider(): array
    {
        return [
            'Divider case' => [
                'case' => ButtonType::Divider,
                'expectedValue' => 'divider',
            ],
            'Info case' => [
                'case' => ButtonType::Info,
                'expectedValue' => 'info',
            ],
            'Link case' => [
                'case' => ButtonType::Link,
                'expectedValue' => 'link',
            ],
            'Menu case' => [
                'case' => ButtonType::Menu,
                'expectedValue' => 'menu',
            ],
        ];
    }

    #[Test]
    #[DataProvider('buttonTypeCasesDataProvider')]
    public function caseHasCorrectStringValue(ButtonType $case, string $expectedValue): void
    {
        self::assertSame($expectedValue, $case->value);
    }

    #[Test]
    public function enumCanBeCreatedFromValidString(): void
    {
        self::assertSame(ButtonType::Divider, ButtonType::from('divider'));
        self::assertSame(ButtonType::Info, ButtonType::from('info'));
        self::assertSame(ButtonType::Link, ButtonType::from('link'));
        self::assertSame(ButtonType::Menu, ButtonType::from('menu'));
    }

    #[Test]
    public function enumTryFromReturnsNullForInvalidString(): void
    {
        self::assertNull(ButtonType::tryFrom('invalid'));
        self::assertNull(ButtonType::tryFrom(''));
        self::assertNull(ButtonType::tryFrom('LINK'));
    }

    #[Test]
    public function enumFromThrowsExceptionForInvalidString(): void
    {
        $this->expectException(ValueError::class);
        ButtonType::from('invalid');
    }

    #[Test]
    public function enumCasesAreStringBacked(): void
    {
        foreach (ButtonType::cases() as $case) {
            self::assertIsString($case->value);
            self::assertNotEmpty($case->value);
        }
    }
}
