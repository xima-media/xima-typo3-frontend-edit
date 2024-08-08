<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;

defined('TYPO3') || die('Access denied.');

ExtensionManagementUtility::addStaticFile(
    Configuration::EXT_KEY,
    'Configuration/TypoScript',
    'Frontend Edit'
);
