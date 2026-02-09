<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\EventListener;

use TYPO3\CMS\Backend\Template\Components\{ButtonBar, ModifyButtonBarEvent};
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Exception\{ExtensionConfigurationExtensionNotConfiguredException, ExtensionConfigurationPathDoesNotExistException};
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\{IconFactory, IconSize};
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Utility\Compatibility\ButtonFactoryUtility;

use function array_key_exists;

/**
 * ModifyButtonBarEventListener.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[AsEventListener(identifier: 'xima-typo3-frontend-edit/backend/modify-button-bar')]
final class ModifyButtonBarEventListener
{
    /** @var array<string, mixed> */
    private array $configuration;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
        $this->configuration = $this->extensionConfiguration->get(Configuration::EXT_KEY);
    }

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        if (!array_key_exists('enableSaveAndCloseButton', $this->configuration) || !(bool) $this->configuration['enableSaveAndCloseButton']) {
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
            $saveCloseButton = ButtonFactoryUtility::createInputButton($buttonBar)
                ->setName('_saveandclosedok')
                ->setValue('1')
                ->setForm($saveButton->getForm())
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'))
                ->setIcon($iconFactory->getIcon('actions-document-save-close', IconSize::SMALL))
                ->setShowLabelText(true)
                ->setDataAttributes([
                    'js' => 'save-close',
                ]);

            /** @var PageRenderer $pageRenderer */
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->loadJavaScriptModule('@xima/ximatypo3frontendedit/save_close.js');

            $buttons[ButtonBar::BUTTON_POSITION_LEFT][2][] = $saveCloseButton;
        }
        $event->setButtons($buttons);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
