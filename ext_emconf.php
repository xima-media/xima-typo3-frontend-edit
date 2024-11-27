<?php

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Frontend Edit',
    'description' => 'This extension provides an edit button for editors within frontend content elements.',
    'category' => 'module',
    'author' => 'Konrad Michalik',
    'author_email' => 'konrad.michalik@xima.de',
    'author_company' => 'XIMA MEDIA GmbH',
    'state' => 'stable',
    'version' => '1.3.0',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.3.99',
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
