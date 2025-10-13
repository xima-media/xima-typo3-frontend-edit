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

use KonradMichalik\PhpCsFixerPreset\Config;
use KonradMichalik\PhpCsFixerPreset\Rules\Header;
use KonradMichalik\PhpCsFixerPreset\Rules\Set\Set;
use KonradMichalik\PhpDocBlockHeaderFixer\Generators\DocBlockHeader;
use KonradMichalik\PhpDocBlockHeaderFixer\Rules\DocBlockHeaderFixer;
use Symfony\Component\Finder\Finder;

return Config::create()
    ->registerCustomFixers([
        new DocBlockHeaderFixer(),
    ])
    ->withRule(
        Header::fromComposer(),
    )
    ->withRule(
        Set::fromArray(
            DocBlockHeader::fromComposer()->__toArray(),
        ),
    )
    ->withFinder(static fn (Finder $finder) => $finder->in(__DIR__))
;
