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
use Xima\XimaTypo3FrontendEdit\Service\Ui\IconDeduplicationService;

/**
 * IconDeduplicationServiceTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(IconDeduplicationService::class)]
final class IconDeduplicationServiceTest extends TestCase
{
    #[Test]
    public function deduplicateReturnsEmptyIconsForEmptyInput(): void
    {
        $result = (new IconDeduplicationService())->deduplicate([]);

        self::assertSame([], $result['contentElements']);
        self::assertSame([], $result['icons']);
    }

    #[Test]
    public function deduplicateReplacesCtypeIconWithAKey(): void
    {
        $contentElements = [
            42 => ['element' => ['uid' => 42, 'ctypeIcon' => '<svg>text</svg>'], 'menu' => []],
        ];

        $result = (new IconDeduplicationService())->deduplicate($contentElements);

        $key = $result['contentElements'][42]['element']['ctypeIcon'];
        self::assertIsString($key);
        self::assertNotSame('<svg>text</svg>', $key);
        self::assertSame('<svg>text</svg>', $result['icons'][$key]);
    }

    #[Test]
    public function deduplicateReplacesNestedMenuChildIcons(): void
    {
        $contentElements = [
            42 => [
                'element' => ['uid' => 42],
                'menu' => [
                    'type' => 'menu',
                    'children' => [
                        'edit' => ['type' => 'link', 'icon' => '<svg>edit</svg>'],
                        'div_action' => ['type' => 'divider'],
                    ],
                ],
            ],
        ];

        $result = (new IconDeduplicationService())->deduplicate($contentElements);

        $key = $result['contentElements'][42]['menu']['children']['edit']['icon'];
        self::assertSame('<svg>edit</svg>', $result['icons'][$key]);
        // A divider has no icon and must pass through unchanged.
        self::assertSame(['type' => 'divider'], $result['contentElements'][42]['menu']['children']['div_action']);
    }

    #[Test]
    public function deduplicateUsesTheSameKeyForIdenticalMarkupAcrossElements(): void
    {
        $contentElements = [
            1 => ['element' => ['uid' => 1, 'ctypeIcon' => '<svg>same</svg>'], 'menu' => []],
            2 => ['element' => ['uid' => 2, 'ctypeIcon' => '<svg>same</svg>'], 'menu' => []],
        ];

        $result = (new IconDeduplicationService())->deduplicate($contentElements);

        self::assertSame(
            $result['contentElements'][1]['element']['ctypeIcon'],
            $result['contentElements'][2]['element']['ctypeIcon'],
        );
        self::assertCount(1, $result['icons']);
    }

    #[Test]
    public function deduplicateUsesDifferentKeysForDifferentMarkup(): void
    {
        $contentElements = [
            1 => ['element' => ['uid' => 1, 'ctypeIcon' => '<svg>a</svg>'], 'menu' => []],
            2 => ['element' => ['uid' => 2, 'ctypeIcon' => '<svg>b</svg>'], 'menu' => []],
        ];

        $result = (new IconDeduplicationService())->deduplicate($contentElements);

        self::assertNotSame(
            $result['contentElements'][1]['element']['ctypeIcon'],
            $result['contentElements'][2]['element']['ctypeIcon'],
        );
        self::assertCount(2, $result['icons']);
    }

    #[Test]
    public function deduplicateLeavesElementsWithoutAnIconUntouched(): void
    {
        $contentElements = [
            42 => ['element' => ['uid' => 42], 'menu' => ['type' => 'menu', 'children' => []]],
        ];

        $result = (new IconDeduplicationService())->deduplicate($contentElements);

        self::assertSame($contentElements, $result['contentElements']);
        self::assertSame([], $result['icons']);
    }
}
