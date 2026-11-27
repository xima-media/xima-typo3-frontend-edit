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

use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * FakeTypo3Version.
 *
 * Stand-in for Typo3Version with a fixed major version, for use with #[WithInstance].
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class FakeTypo3Version extends Typo3Version
{
    public function __construct(
        private readonly int $majorVersion,
    ) {}

    public function getMajorVersion(): int
    {
        return $this->majorVersion;
    }
}
