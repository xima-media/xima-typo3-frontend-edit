<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Enumerations\ButtonType;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;
use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

final class MenuGenerator
{
    protected array $configuration = [];

    public function __construct(protected readonly IconFactory $iconFactory, protected readonly EventDispatcher $eventDispatcher)
    {
        $setup = $GLOBALS['TSFE']->tmpl->setup;
        if (!isset($setup['plugin.']['tx_ximatypo3frontendedit.'])) {
            return;
        }
        $this->configuration = $setup['plugin.']['tx_ximatypo3frontendedit.'];
    }

    public function getDropdown(int $pid, string $returnUrl, int $languageUid): array
    {
        $ignoredPids = $this->configuration['settings.']['ignorePids'] ? explode(',', $this->configuration['settings.']['ignorePids']) : [];
        foreach ($ignoredPids as $ignoredPid) {
            if ($this->isSubpageOf($pid, $ignoredPid)) {
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

        if ($backendUser->user['tx_ximatypo3frontendedit_hide']) {
            return [];
        }

        $ignoredCTypes = $this->configuration['settings.']['ignoreCTypes'] ? explode(',', $this->configuration['settings.']['ignoreCTypes']) : [];

        $result = [];
        foreach ($this->fetchContentElements($pid, $languageUid) as $contentElement) {
            // ToDo: is this sufficient?
            if (!$backendUser->recordEditAccessInternals('tt_content', $contentElement['uid'])) {
                continue;
            }

            if (in_array($contentElement['CType'], $ignoredCTypes, true)) {
                continue;
            }

            $contentElementConfig = $this->getContentElementConfig($contentElement['CType'], $contentElement['list_type']);

            $menuButton = new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_menu',
                    ButtonType::Menu,
                icon: $this->iconFactory->getIcon('actions-open', 'small')
            );

            $menuButton->addChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_info',
                ButtonType::Divider
            ));

            $menuButton->addChild(new Button(
                $GLOBALS['LANG']->sL($contentElementConfig['label']) . '<p><small><strong>[' . $contentElement['uid'] . ']</strong> ' . ($contentElement['header'] ? (strlen($contentElement['header']) > 30 ? substr($contentElement['header'], 0, 30) . '...' : $contentElement['header']) : '') . '</small></p>',
                ButtonType::Header,
                icon: $this->iconFactory->getIcon($contentElementConfig['icon'], 'small')
            ));

            $menuButton->addChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_edit',
                ButtonType::Divider
            ));

            $menuButton->addChild(new Button(
                $contentElement['CType'] === 'list' ? 'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_plugin' : 'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_content_element',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => [
                            'tt_content' => [
                                $contentElement['uid'] => 'edit',
                            ],
                        ],
                        'returnUrl' => $returnUrl . '#c' . $contentElement['uid'],
                    ],
                )->__toString(),
                $this->iconFactory->getIcon($contentElement['CType'] === 'list' ? 'content-plugin' : 'content-textpic', 'small')
            ));

            $menuButton->addChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_pages',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'web_layout',
                    [
                        'id' => $pid,
                        'returnUrl' => $returnUrl . '#c' . $contentElement['uid'],
                    ],
                )->__toString(),
                $this->iconFactory->getIcon('apps-pagetree-page-default', 'small')
            ));

            $menuButton->addChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_action',
                ButtonType::Divider
            ));

            $menuButton->addChild(new Button(
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
                        'returnUrl' => $returnUrl . '#c' . $contentElement['uid'],
                    ],
                )->__toString(),
                $this->iconFactory->getIcon('actions-toggle-on', 'small')
            ));

            $menuButton->addChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:info',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'show_item',
                    [
                        'uid' => $contentElement['uid'],
                        'table' => 'tt_content',
                        'returnUrl' => $returnUrl . '#c' . $contentElement['uid'],
                    ],
                )->__toString(),
                $this->iconFactory->getIcon('actions-info', 'small')
            ));


            $menuButton->addChild(new Button(
                'LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:history',
                ButtonType::Link,
                GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    'record_history',
                    [
                        'element' => 'tt_content:' . $contentElement['uid'],
                        'returnUrl' => $returnUrl . '#c' . $contentElement['uid'],
                    ],
                )->__toString(),
                $this->iconFactory->getIcon('actions-history', 'small')
            ));

            $result[$contentElement['uid']] = [
                'element' => $contentElement,
                'menu' => $menuButton,
            ];
        }

        $this->eventDispatcher->dispatch(new FrontendEditDropdownModifyEvent($result));

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

    private function isSubpageOf($subPageId, $parentPageId)
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pageRepository->init(false);
        $rootline = $pageRepository->getRootLine($subPageId);

        foreach ($rootline as $page) {
            if ((int)$page['uid'] === (int)$parentPageId) {
                return true;
            }
        }

        return false;
    }
}
