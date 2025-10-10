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

use Xima\XimaTypo3FrontendEdit\Domain\Model\BackendUser;

return [
    BackendUser::class => [
        'tableName' => 'be_users',
        'properties' => [
            'disable' => [
                'fieldName' => 'tx_ximatypo3frontendedit_disable',
            ],
        ],
    ],
];
