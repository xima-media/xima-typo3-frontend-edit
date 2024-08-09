<?php

use Xima\XimaTypo3FrontendEdit\Domain\Model\BackendUser;

return [
    BackendUser::class => [
        'tableName' => 'be_users',
        'properties' => [
            'hide' => [
                'fieldName' => 'tx_ximatypo3frontendedit_hide',
            ],
        ],
    ],
];
