<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;

defined('TYPO3') or die();

$GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_ximatypo3frontendedit_hide'] = [
    'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_db.xlf:be_users.tx_ximatypo3frontendedit_hide',
    'description' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_db.xlf:be_users.tx_ximatypo3frontendedit_hide.description',
    'type' => 'check',
    'table' => 'be_users',
];

ExtensionManagementUtility::addFieldsToUserSettings(
    'tx_ximatypo3frontendedit_hide',
    'after:copyLevels',
);
