<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

final class MenuGenerator
{
    protected array $configuration = [];

    public function __construct(protected readonly IconFactory $iconFactory, protected readonly EventDispatcher $eventDispatcher)
    {
        $this->getSettings();
    }

    public function getDropdown(int $pid, string $returnUrl, int $languageUid, array $data = []): array
    {
        $ignoredPids = $this->configuration['ignorePids'] ? explode(',', $this->configuration['ignorePids']) : [];
        foreach ($ignoredPids as $ignoredPid) {
            if ($this->isSubpageOf($pid, (int)$ignoredPid)) {
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

        if ($backendUser->user['tx_ximatypo3frontendedit_disable']) {
            return [];
        }

        $ignoredCTypes = $this->configuration['ignoreCTypes'] ? explode(',', $this->configuration['ignoreCTypes']) : [];
        $ignoredListTypes = $this->configuration['ignoreListTypes'] ? explode(',', $this->configuration['ignoreListTypes']) : [];
        $ignoredUids = $this->configuration['ignoredUids'] ? explode(',', $this->configuration['ignoredUids']) : [];

        $result = [];
        foreach ($this->fetchContentElements($pid, $languageUid) as $contentElement) {
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

            $contentElementConfig = $this->getContentElementConfig($contentElement['CType'], $contentElement['list_type']);
            $returnUrlAnchor = $returnUrl . '#c' . $contentElement['uid'];

            $menuButton = new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_menu',
                ButtonType::Menu,
                icon: $this->iconFactory->getIcon('actions-open', 'small')
            );

            /*
            * Info
            */
            $menuButton->appendChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_info',
                ButtonType::Divider
            ), 'div_info');

            $menuButton->appendChild(new Button(
                $GLOBALS['LANG']->sL($contentElementConfig['label']) . '<p><small><strong>[' . $contentElement['uid'] . ']</strong> ' . ($contentElement['header'] ? $this->shortenString($contentElement['header']) : '') . '</small></p>',
                ButtonType::Info,
                icon: $this->iconFactory->getIcon($contentElementConfig['icon'], 'small')
            ), 'header');

            /*
            * Edit
            */
            $menuButton->appendChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_edit',
                ButtonType::Divider
            ), 'div_edit');

            $menuButton->appendChild(new Button(
                $contentElement['CType'] === 'list' ? 'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_plugin' : 'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_content_element',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => [
                            'tt_content' => [
                                $contentElement['uid'] => 'edit',
                            ],
                            'language' => $languageUid,
                        ],
                        'returnUrl' => $returnUrlAnchor,
                    ],
                )->__toString(),
                $this->iconFactory->getIcon($contentElement['CType'] === 'list' ? 'content-plugin' : 'content-textpic', 'small')
            ), 'edit');

            $menuButton->appendChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_page',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'web_layout',
                    [
                        'id' => $pid,
                        'language' => $languageUid,
                        'returnUrl' => $returnUrlAnchor,
                    ],
                )->__toString(),
                $this->iconFactory->getIcon('apps-pagetree-page-default', 'small')
            ), 'edit_page');

            /*
            * Action
            */
            $menuButton->appendChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_action',
                ButtonType::Divider
            ), 'div_action');

            $menuButton->appendChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:hide',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
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
                $this->iconFactory->getIcon('actions-toggle-on', 'small')
            ), 'hide');

            $menuButton->appendChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:info',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'show_item',
                    [
                        'uid' => $contentElement['uid'],
                        'table' => 'tt_content',
                        'returnUrl' => $returnUrlAnchor,
                    ],
                )->__toString(),
                $this->iconFactory->getIcon('actions-info', 'small')
            ), 'info');

            $menuButton->appendChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:history',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'record_history',
                    [
                        'element' => 'tt_content:' . $contentElement['uid'],
                        'returnUrl' => $returnUrlAnchor,
                    ],
                )->__toString(),
                $this->iconFactory->getIcon('actions-history', 'small')
            ), 'history');

            /*
            * Data
            */
            if (array_key_exists($contentElement['uid'], $data) && !empty($data[$contentElement['uid']])) {
                $menuButton->appendChild(new Button(
                    'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_data',
                    ButtonType::Divider
                ), 'div_data');

                foreach ($data[$contentElement['uid']] as $key => $dataEntry) {
                    if (!$dataEntry['label'] || !(($dataEntry['table'] && $dataEntry['uid']) || ($dataEntry['url']))) {
                        continue;
                    }

                    if ($dataEntry['table'] && $dataEntry['uid']) {
                        if (!$backendUser->recordEditAccessInternals($dataEntry['table'], $dataEntry['uid'])) {
                            continue;
                        }
                    }

                    $url = $dataEntry['url'] ?: GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit' => [
                                $dataEntry['table'] => [
                                    $dataEntry['uid'] => 'edit',
                                ],
                                'language' => $languageUid,
                            ],
                            'returnUrl' => $returnUrlAnchor,
                        ],
                    )->__toString();
                    $icon = $dataEntry['icon'] ? $this->iconFactory->getIcon($dataEntry['icon'], 'small') : $this->iconFactory->getIcon($contentElementConfig['icon'], 'small');

                    $menuButton->appendChild(new Button(
                        $this->shortenString($dataEntry['label']),
                        ButtonType::Link,
                        $url,
                        $icon
                    ), 'data_' . $key);
                }
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

    private function fetchContentElements(int $pid, int $languageUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        return $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)),
            )
            ->executeQuery()->fetchAllAssociative();
    }

    private function getContentElementConfig(string $cType, string $listType): array|bool
    {
        $tca = $cType === 'list' ? $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] : $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];

        foreach ($tca as $item) {
            if (($cType === 'list' && $item['value'] === $listType) || $item['value'] === $cType) {
                return $item;
            }
        }

        return false;
    }

    private function isSubpageOf(int $subPageId, int $parentPageId): bool
    {
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $subPageId)->get();
        foreach ($rootLine as $page) {
            if ((int)$page['uid'] === (int)$parentPageId) {
                return true;
            }
        }

        return false;
    }

    private function shortenString(string $string, int $maxLength = 30): string
    {
        return strlen($string) > $maxLength ? substr($string, 0, $maxLength) . '…' : $string;
    }

    private function getSettings(): void {
        $request = $GLOBALS['TYPO3_REQUEST'];
        try {
            $fullTypoScript = $request->getAttribute('frontend.typoscript')->getSetupArray();
        } catch (\Exception $e) {
            // An exception is thrown, when TypoScript setup array is not available. This is usually the case,
            // when the current page request is cached. Therefore, the TSFE TypoScript parsing is forced here.
            // Set a TypoScriptAspect which forces template parsing
            GeneralUtility::makeInstance(Context::class)
                ->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));
            // Call TSFE getFromCache, which re-processes TypoScript respecting $forcedTemplateParsing property
            // from TypoScriptAspect
            $tsfe = $request->getAttribute('frontend.controller');
            $requestWithFullTypoScript = $tsfe->getFromCache($request);
            $fullTypoScript = $requestWithFullTypoScript->getAttribute('frontend.typoscript')->getSetupArray();
        }
        $settings = $fullTypoScript['plugin.']['tx_ximatypo3frontendedit.']['settings.'] ?? [];
        $this->configuration = GeneralUtility::removeDotsFromTS($settings);
    }
}
