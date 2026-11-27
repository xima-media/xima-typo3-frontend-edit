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

namespace Xima\XimaTypo3FrontendEdit\Utility;

use function array_flip;
use function array_intersect_key;

/**
 * ElementPayloadUtility.
 *
 * The element of an edit information response is built from a database row. Only
 * the fields the frontend reads and the ones set by the extension itself leave
 * the server, never the rest of the row.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ElementPayloadUtility
{
    private const ALLOWED_KEYS = [
        'uid',
        'pid',
        'header',
        'CType',
        'colPos',
        'tx_container_parent',
        'l18n_parent',
        'l10n_source',
        'ctypeLabel',
        'ctypeIcon',
        '_table',
        '_ext',
        'newBeforeUrl',
        'newAfterUrl',
    ];

    /**
     * @param array<string, mixed> $element
     *
     * @return array<string, mixed>
     */
    public static function restrict(array $element): array
    {
        return array_intersect_key($element, array_flip(self::ALLOWED_KEYS));
    }
}
