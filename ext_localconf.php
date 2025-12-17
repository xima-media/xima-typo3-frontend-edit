<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['xtfe'] = ['Xima\\XimaTypo3FrontendEdit\\ViewHelpers'];

// Register PAGE types for AJAX endpoints
ExtensionManagementUtility::addTypoScriptSetup('
ximaFrontendEditInformation = PAGE
ximaFrontendEditInformation {
  typeNum = 1729341864
  config {
    disableAllHeaderCode = 1
    additionalHeaders.10.header = Content-Type: application/json; charset=utf-8
    admPanel = 0
    debug = 0
  }
  10 = COA_INT
  10 {
    10 = TEXT
    10.value = {}
  }
}
');
