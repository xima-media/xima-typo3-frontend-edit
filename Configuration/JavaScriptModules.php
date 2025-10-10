<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'imports' => [
        '@xima/ximatypo3frontendedit/save_close.js' => 'EXT:xima_typo3_frontend_edit/Resources/Public/JavaScript/save_close.js',
    ],
];
