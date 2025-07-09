<?php

namespace Xima\XimaTypo3FrontendEdit\EventListener;

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Utility\IconUtility;

final class ModifyButtonBarEventListener
{
    protected array $configuration;
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration
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
                ->setIcon($iconFactory->getIcon('actions-document-save-close', IconUtility::getDefaultIconSize()))
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
