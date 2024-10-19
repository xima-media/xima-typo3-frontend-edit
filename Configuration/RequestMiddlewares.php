<?php

return [
    'frontend' => [
        'xima/frontend-edit-information' => [
            'target' => \Xima\XimaTypo3FrontendEdit\Middleware\EditInformationMiddleware::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
        'xima/frontend-edit-tool' => [
            'target' => \Xima\XimaTypo3FrontendEdit\Middleware\ToolRendererMiddleware::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
];
