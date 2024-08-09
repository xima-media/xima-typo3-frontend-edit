<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Controller\EditController;

ExtensionUtility::configurePlugin(
    Configuration::EXT_NAME,
    Configuration::PLUGIN_NAME,
    [
        EditController::class => 'contentElements',
    ],
    [
        EditController::class => 'contentElements',
    ]
);
