<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;
use Xima\XimaTypo3FrontendEdit\Utility\ContentUtility;

final class MenuGenerator
{
    protected array $configuration = [];

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly EventDispatcher $eventDispatcher,
        protected readonly SettingsService $settingsService,
        protected readonly ExtensionConfiguration $extensionConfiguration
    ) {
        $this->configuration = $this->extensionConfiguration->get(Configuration::EXT_KEY);
    }

    public function getDropdown(int $pid, string $returnUrl, int $languageUid, array $data = []): array
    {
        $ignoredPids = $this->settingsService->getIgnoredPids();
        foreach ($ignoredPids as $ignoredPid) {
            if (ContentUtility::isSubpageOf($pid, (int)$ignoredPid)) {
                return [];
            }
        }

        /* @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
        $backendUser = $GLOBALS['BE_USER'];
        if ($backendUser->user === null) {
            Bootstrap::initializeBackendAuthentication();
            $backendUser->initializeUserSessionManager();
            $backendUser = $GLOBALS['BE_USER'];
        }

        if (array_key_exists('tx_ximatypo3frontendedit_disable', $backendUser->user) && $backendUser->user['tx_ximatypo3frontendedit_disable']) {
            return [];
        }

        $ignoredCTypes = $this->settingsService->getIgnoredCTypes();
        $ignoredListTypes = $this->settingsService->getIgnoredListTypes();
        $ignoredUids = $this->settingsService->getIgnoredUids();

        $result = [];
        foreach (ContentUtility::fetchContentElements($pid, $languageUid) as $contentElement) {
            // ToDo: is this sufficient?
            if (!$backendUser->recordEditAccessInternals('tt_content', $contentElement['uid'])) {
                continue;
            }

            if (in_array($contentElement['uid'], $ignoredUids, true)) {
                continue;
            }

            if (in_array($contentElement['CType'], $ignoredCTypes, true)) {
                continue;
            }

            if ($contentElement['CType'] === 'list' && in_array($contentElement['list_type'], $ignoredListTypes, true)) {
                continue;
            }

            $contentElementConfig = ContentUtility::getContentElementConfig($contentElement['CType'], $contentElement['list_type']);
            $returnUrlAnchor = $returnUrl . '#c' . $contentElement['uid'];

            $simpleMode = (array_key_exists('simpleMode', $this->configuration) && $this->configuration['simpleMode']) || $this->settingsService->checkSimpleModeMenuStructure();

            if ($simpleMode) {
                $menuButton = new Button(
                    'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_menu',
                    ButtonType::Link,
                    url: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit' => [
                                'tt_content' => [
                                    $contentElement['uid'] => 'edit',
                                ],
                                'language' => $languageUid,
                            ],
                            'returnUrl' => $returnUrlAnchor,
                        ]
                    )->__toString(),
                    icon: $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL),
                    targetBlank: array_key_exists('linkTargetBlank', $this->configuration) && $this->configuration['linkTargetBlank']
                );
            } else {
                $menuButton = new Button(
                    'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_menu',
                    ButtonType::Menu,
                    icon: $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)
                );

                /*
                * Info
                */
                $this->processNewButton($menuButton, 'div_info', ButtonType::Divider);

                $additionalUid = $GLOBALS['BE_USER']->isAdmin() ? ' <code>[' . $contentElement['uid'] . ']</code>' : '';
                $this->processNewButton(
                    $menuButton,
                    'header',
                    ButtonType::Info,
                    label: $GLOBALS['LANG']->sL($contentElementConfig['label']) . '<p><small>' . ($contentElement['header'] ? ContentUtility::shortenString($contentElement['header']) : '') . $additionalUid . '</small></p>',
                    icon: $contentElementConfig['icon']
                );

                /*
                * Edit
                */
                $this->processNewButton($menuButton, 'div_edit', ButtonType::Divider);
                $this->processNewButton(
                    $menuButton,
                    'edit',
                    ButtonType::Link,
                    label: $contentElement['CType'] === 'list' ? 'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_plugin' : 'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_content_element',
                    url: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit' => [
                                'tt_content' => [
                                    $contentElement['uid'] => 'edit',
                                ],
                                'language' => $languageUid,
                            ],
                            'returnUrl' => $returnUrlAnchor,
                        ]
                    )->__toString()
                    . '&tx_ximatypo3frontendedit', // add custom parameter to identify the request and render the save and close button in the edit form
                    icon: $contentElement['CType'] === 'list' ? 'content-plugin' : 'content-textpic'
                );
                $this->processNewButton(
                    $menuButton,
                    'edit_page',
                    ButtonType::Link,
                    url: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'web_layout',
                        [
                            'id' => $pid,
                            'language' => $languageUid,
                            'returnUrl' => $returnUrlAnchor,
                        ],
                    )->__toString(),
                    icon: 'apps-pagetree-page-default'
                );

                /*
                * Action
                */
                $this->processNewButton($menuButton, 'div_action', ButtonType::Divider);
                $this->processNewButton(
                    $menuButton,
                    'hide',
                    ButtonType::Link,
                    url: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'tce_db',
                        [
                            'data' => [
                                'tt_content' => [
                                    $contentElement['uid'] => [
                                        'hidden' => 1,
                                    ],
                                ],
                            ],
                            'redirect' => $returnUrlAnchor,
                        ],
                    )->__toString(),
                    icon: 'actions-toggle-on'
                );
                $this->processNewButton(
                    $menuButton,
                    'info',
                    ButtonType::Link,
                    url: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'show_item',
                        [
                            'uid' => $contentElement['uid'],
                            'table' => 'tt_content',
                            'returnUrl' => $returnUrlAnchor,
                        ],
                    )->__toString(),
                    icon: 'actions-info'
                );
                $this->processNewButton(
                    $menuButton,
                    'move',
                    ButtonType::Link,
                    url: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'move_element',
                        [
                            'uid' => $contentElement['uid'],
                            'table' => 'tt_content',
                            'expandPage' => $pid,
                            'returnUrl' => $returnUrlAnchor,
                        ],
                    )->__toString(),
                    icon: 'actions-move'
                );
                $this->processNewButton(
                    $menuButton,
                    'history',
                    ButtonType::Link,
                    url: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'record_history',
                        [
                            'element' => 'tt_content:' . $contentElement['uid'],
                            'returnUrl' => $returnUrlAnchor,
                        ],
                    )->__toString(),
                    icon: 'actions-history'
                );

                /*
                * Data
                */
                $this->handleAdditionalData($menuButton, $contentElement, $contentElementConfig, $data, $backendUser, $languageUid, $returnUrlAnchor);
            }
            /*
            * Event
            */
            $this->eventDispatcher->dispatch(new FrontendEditDropdownModifyEvent($contentElement, $menuButton, $returnUrlAnchor));
            $result[$contentElement['uid']] = [
                'element' => $contentElement,
                'menu' => $menuButton,
            ];
        }

        foreach ($result as $contentElement) {
            $result[$contentElement['element']['uid']]['menu'] = $contentElement['menu']->render();
        }
        return $result;
    }

    private function processNewButton(Button &$button, string $identifier, ButtonType $type, ?string $label = null, ?string $url = null, ?string $icon = null): void
    {
        if (!$this->settingsService->checkDefaultMenuStructure($identifier)) {
            return;
        }

        $button->appendChild(new Button(
            $label ?: "LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:$identifier",
            $type,
            $url,
            $icon ? $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL) : null,
            array_key_exists('linkTargetBlank', $this->configuration) && $this->configuration['linkTargetBlank']
        ), $identifier);
    }

    private function handleAdditionalData(Button $button, array $contentElement, array $contentElementConfig, array $data, BackendUserAuthentication $backendUser, int $languageUid, string $returnUrlAnchor): void
    {
        if ((array_key_exists($contentElement['uid'], $data) && ($uid = $contentElement['uid']) && !empty($data[$uid])) ||
            (array_key_exists('l10n_source', $contentElement) && array_key_exists($contentElement['l10n_source'], $data) && ($uid = $contentElement['l10n_source']) && !empty($data[$uid]))
        ) {
            $button->appendChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_data',
                ButtonType::Divider
            ), 'div_data');

            foreach ($data[$uid] as $key => $dataEntry) {
                if (!$dataEntry['label'] || !(($dataEntry['table'] && $dataEntry['uid']) || ($dataEntry['url']))) {
                    continue;
                }

                $recordUid = null;
                if ($dataEntry['table'] && $dataEntry['uid']) {
                    if (!$backendUser->recordEditAccessInternals($dataEntry['table'], $dataEntry['uid'])) {
                        continue;
                    }
                    $recordUid = $dataEntry['uid'];

                    /*
                    * Check if the record is translated and if so, get the translation
                    */
                    $record = BackendUtility::getRecord($dataEntry['table'], $recordUid);
                    if ($record && array_key_exists('sys_language_uid', $record) && $record['sys_language_uid'] !== $languageUid) {
                        $record = ContentUtility::getTranslatedRecord($dataEntry['table'], $recordUid, $languageUid);
                        if ($record) {
                            $recordUid = $record['uid'];
                        } else {
                            continue;
                        }
                    }
                }

                $url = $dataEntry['url'] ?: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => [
                            $dataEntry['table'] => [
                                $recordUid => 'edit',
                            ],
                            'language' => $languageUid,
                        ],
                        'returnUrl' => $returnUrlAnchor,
                    ],
                )->__toString();
                $icon = $dataEntry['icon'] ? $this->iconFactory->getIcon($dataEntry['icon'], Icon::SIZE_SMALL) : $this->iconFactory->getIcon($contentElementConfig['icon'], Icon::SIZE_SMALL);

                $button->appendChild(new Button(
                    ContentUtility::shortenString($dataEntry['label']),
                    ButtonType::Link,
                    $url,
                    $icon
                ), 'data_' . $key);
            }
        }
    }
}
