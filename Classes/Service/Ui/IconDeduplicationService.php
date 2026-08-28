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

namespace Xima\XimaTypo3FrontendEdit\Service\Ui;

use function hash;
use function is_array;
use function is_string;
use function substr;

/**
 * IconDeduplicationService.
 *
 * The rendered `contentElements` structure embeds icon SVG markup once per
 * button per content element - on a page with more than a handful of
 * elements, the same handful of icons (edit, info, history, ...) end up
 * repeated dozens of times, dominating the editInformation AJAX payload
 * (see issue #217: measured ~38% of response bytes on a small demo page,
 * growing with element count since the set of distinct icons stays roughly
 * constant while occurrences scale with the page).
 *
 * Deliberately a pure post-processing step on the already-rendered
 * structure (not a change to Button::render()'s own output), so the page
 * menu / sticky toolbar - a single menu per page load, with no meaningful
 * repetition to deduplicate - is entirely unaffected.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class IconDeduplicationService
{
    /**
     * @param array<int|string, array{element: array<string, mixed>, menu: array<string, mixed>}> $contentElements
     *
     * @return array{contentElements: array<int|string, mixed>, icons: array<string, string>}
     */
    public function deduplicate(array $contentElements): array
    {
        $icons = [];

        foreach ($contentElements as $uid => $contentElement) {
            if (isset($contentElement['element']['ctypeIcon']) && is_string($contentElement['element']['ctypeIcon'])) {
                [$contentElements[$uid]['element']['ctypeIcon'], $icons] = $this->replaceWithKey($contentElement['element']['ctypeIcon'], $icons);
            }
            [$contentElements[$uid]['menu'], $icons] = $this->deduplicateMenu($contentElement['menu'], $icons);
        }

        return ['contentElements' => $contentElements, 'icons' => $icons];
    }

    /**
     * @param array<string, mixed>  $menu
     * @param array<string, string> $icons
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function deduplicateMenu(array $menu, array $icons): array
    {
        if (isset($menu['icon']) && is_string($menu['icon'])) {
            [$menu['icon'], $icons] = $this->replaceWithKey($menu['icon'], $icons);
        }

        if (isset($menu['children']) && is_array($menu['children'])) {
            foreach ($menu['children'] as $key => $child) {
                if (is_array($child)) {
                    [$menu['children'][$key], $icons] = $this->deduplicateMenu($child, $icons);
                }
            }
        }

        return [$menu, $icons];
    }

    /**
     * @param array<string, string> $icons
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function replaceWithKey(string $markup, array $icons): array
    {
        $key = substr(hash('xxh128', $markup), 0, 8);
        $icons[$key] ??= $markup;

        return [$key, $icons];
    }
}
