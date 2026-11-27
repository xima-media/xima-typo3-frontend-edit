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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures;

use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * FakeLanguageService.
 *
 * Constructor-only stand-in for LanguageService, for use with #[WithGlobal('LANG', ...)]:
 * sL() returns the given translation, or the label itself without one.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class FakeLanguageService extends LanguageService
{
    public function __construct(
        private readonly ?string $translation = null,
    ) {}

    public function sL($input): string
    {
        return $this->translation ?? (string) $input;
    }
}
