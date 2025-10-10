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
    'frontend' => [
        'xima/frontend-edit-information' => [
            'target' => Xima\XimaTypo3FrontendEdit\Middleware\EditInformationMiddleware::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
        'xima/frontend-edit-tool' => [
            'target' => Xima\XimaTypo3FrontendEdit\Middleware\ToolRendererMiddleware::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
];
