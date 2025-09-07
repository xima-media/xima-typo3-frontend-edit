<?php

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

namespace Xima\XimaTypo3FrontendEdit\EventListener;

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\VersionCompatibilityService;

/**
 * ModifyButtonBarEventListener.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
final class ModifyButtonBarEventListener
{
    protected array $configuration;

    /**
    * @throws ExtensionConfigurationPathDoesNotExistException
    * @throws ExtensionConfigurationExtensionNotConfiguredException
    */
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly VersionCompatibilityService $versionCompatibilityService
    ) {
        $this->configuration = $this->extensionConfiguration->get(Configuration::EXT_KEY);
    }

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        if (!array_key_exists('enableSaveAndCloseButton', $this->configuration) && !$this->configuration['enableSaveAndCloseButton']) {
            return;
        }

        if (!array_key_exists('tx_ximatypo3frontendedit', $GLOBALS['TYPO3_REQUEST']->getQueryParams())) {
            return;
        }

        $buttons = $event->getButtons();
        $buttonBar = $event->getButtonBar();
        $saveButton = $buttons[ButtonBar::BUTTON_POSITION_LEFT][2][0] ?? null;

        if ($saveButton instanceof InputButton) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $saveCloseButton = $buttonBar->makeInputButton()
                ->setName('_saveandclosedok')
                ->setValue('1')
                ->setForm($saveButton->getForm())
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'))
                ->setIcon($iconFactory->getIcon('actions-document-save-close', $this->versionCompatibilityService->getDefaultIconSize()))
                ->setShowLabelText(true);

            $typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
            if ($typo3Version >= 13) {
                $saveCloseButton->setDataAttributes([
                    'js' => 'save-close',
                ]);

                /** @var PageRenderer $pageRenderer */
                $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
                $pageRenderer->loadJavaScriptModule('@xima/ximatypo3frontendedit/save_close.js');
            }

            $buttons[ButtonBar::BUTTON_POSITION_LEFT][2][] = $saveCloseButton;
        }
        $event->setButtons($buttons);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
