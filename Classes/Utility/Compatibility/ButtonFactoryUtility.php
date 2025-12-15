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

namespace Xima\XimaTypo3FrontendEdit\Utility\Compatibility;

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ButtonFactoryUtility.
 *
 * Provides TYPO3 v13/v14 compatibility for button creation.
 * In TYPO3 v14+, buttons should be created via ButtonFactory.
 * In TYPO3 v13, buttons are created via ButtonBar::makeInputButton().
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class ButtonFactoryUtility
{
    public static function createInputButton(ButtonBar $buttonBar): InputButton
    {
        if (VersionUtility::is14OrHigher()) {
            /** @phpstan-ignore method.notFound (ButtonFactory only exists in TYPO3 v14+) */
            return self::getButtonFactory()->createInputButton();
        }

        /** @phpstan-ignore method.deprecated (Required for TYPO3 v13 compatibility) */
        return $buttonBar->makeInputButton();
    }

    private static function getButtonFactory(): object
    {
        // Use dynamic class name to avoid autoload issues on TYPO3 13
        /** @phpstan-ignore argument.type */
        return GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\Components\\ButtonFactory');
    }
}
