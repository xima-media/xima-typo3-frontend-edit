<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;

defined('TYPO3') or die();

$GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_ximatypo3frontendedit_disable'] = [
    'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_db.xlf:be_users.tx_ximatypo3frontendedit_disable',
    'description' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_db.xlf:be_users.tx_ximatypo3frontendedit_disable.description',
    'type' => 'check',
    'table' => 'be_users',
];

ExtensionManagementUtility::addFieldsToUserSettings(
    'tx_ximatypo3frontendedit_disable',
    'after:copyLevels',
);
