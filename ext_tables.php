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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;

(static function (): void {
    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_ximatypo3frontendedit_disable'] = [
        'label' => 'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang_db.xlf:be_users.tx_ximatypo3frontendedit_disable',
        'description' => 'LLL:EXT:'.Configuration::EXT_KEY.'/Resources/Private/Language/locallang_db.xlf:be_users.tx_ximatypo3frontendedit_disable.description',
        'type' => 'check',
        'table' => 'be_users',
    ];

    ExtensionManagementUtility::addFieldsToUserSettings(
        'tx_ximatypo3frontendedit_disable',
        'after:copyLevels',
    );
})();
