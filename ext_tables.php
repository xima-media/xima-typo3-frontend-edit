<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "xima_typo3_frontend_edit".
 *
 * Copyright (C) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;

(static function (): void {
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
})();
