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

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['xtfe'] = ['Xima\\XimaTypo3FrontendEdit\\ViewHelpers'];
