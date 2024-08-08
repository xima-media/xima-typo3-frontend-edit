<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Event\FrontendEditDropdownModifyEvent;

final class EditController
{
    public function getContentElementsByPage(ServerRequestInterface $request): ResponseInterface
    {
        $pid = $request->getQueryParams()['pid']
            ?? throw new \InvalidArgumentException(
                'Please provide pid',
                1722599959,
            );
        $returnUrl = $request->getQueryParams()['returnUrl'] ? strtok(urldecode($request->getQueryParams()['returnUrl']), '#') : '';
        $language_uid = $request->getQueryParams()['language_uid'] ?? 0;

        /* @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
        $backendUser = $GLOBALS['BE_USER'];
        if ($backendUser->user === null) {
            Bootstrap::initializeBackendAuthentication();
            $backendUser->initializeUserSessionManager();
            $backendUser = $GLOBALS['BE_USER'];
        }

        if (!BackendUtility::readPageAccess(
            $pid,
            $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
        )) {
            return new JsonResponse([]);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        $contentElements = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language_uid, Connection::PARAM_INT)),
            )
            ->executeQuery()->fetchAllAssociative();

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $result = [];
        foreach ($contentElements as $contentElement) {
            // ToDo: is this sufficient?
            if (!$backendUser->recordEditAccessInternals('tt_content', $contentElement['uid'])) {
                continue;
            }

            $contentElementConfig = $this->getContentElementConfig($contentElement['CType'], $contentElement['list_type']);
            $result[$contentElement['uid']] = [
                'uid' => $contentElement['uid'],
                'type' => $contentElement['CType'],
                'label' => $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_menu'),
                'icon' => $iconFactory->getIcon('actions-open', 'small')->getAlternativeMarkup('inline'),
                'actions' => [
                    'div_info' => [
                        'type' => 'divider',
                        'label' => $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_info'),
                    ],
                    'intro' => [
                        'type' => 'header',
                        'label' => $GLOBALS['LANG']->sL($contentElementConfig['label']) . '<p><small><strong>[' . $contentElement['uid'] . ']</strong> ' . ($contentElement['header'] ? (strlen($contentElement['header']) > 30 ? substr($contentElement['header'], 0, 30) . '...' : $contentElement['header']) : '') . '</small></p>',
                        'icon' => $iconFactory->getIcon($contentElementConfig['icon'], 'small')->getAlternativeMarkup('inline'),
                    ],
                    'div_edit' => [
                        'type' => 'divider',
                        'label' => $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_edit'),
                    ],
                    'edit' => [
                        'type' => 'link',
                        'label' => $contentElement['CType'] === 'list' ? $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_plugin') : $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_content_element'),
                        'icon' => $iconFactory->getIcon($contentElement['CType'] === 'list' ? 'content-plugin' : 'content-textpic', 'small')->getAlternativeMarkup('inline'),
                        'url' => GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
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
                    ],
                    'page' => [
                        'type' => 'link',
                        'label' => $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:edit_pages'),
                        'icon' => $iconFactory->getIcon('apps-pagetree-page-default', 'small')->getAlternativeMarkup('inline'),
                        'url' => GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                            'web_layout',
                            [
                                'id' => $pid,
                                'returnUrl' => $returnUrl . '#c' . $contentElement['uid'],
                            ],
                        )->__toString(),
                    ],
                    'div_action' => [
                        'type' => 'divider',
                        'label' => $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:div_action'),
                    ],
                    'hide' => [
                        'type' => 'link',
                        'label' => $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:hide'),
                        'icon' => $iconFactory->getIcon('actions-toggle-on', 'small')->getAlternativeMarkup('inline'),
                        'url' => GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
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
                    ],
                    'info' => [
                        'type' => 'link',
                        'label' => $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:info'),
                        'icon' => $iconFactory->getIcon('actions-info', 'small')->getAlternativeMarkup('inline'),
                        'url' => GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                            'show_item',
                            [
                                'uid' => $contentElement['uid'],
                                'table' => 'tt_content',
                                'returnUrl' => $returnUrl . '#c' . $contentElement['uid'],
                            ],
                        )->__toString(),
                    ],
                    'history' => [
                        'type' => 'link',
                        'label' => $GLOBALS['LANG']->sL('LLL:EXT:xima_typo3_frontend_edit/Resources/Private/Language/locallang.xlf:history'),
                        'icon' => $iconFactory->getIcon('actions-history', 'small')->getAlternativeMarkup('inline'),
                        'url' => GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                            'record_history',
                            [
                                'element' => 'tt_content:' . $contentElement['uid'],
                                'returnUrl' => $returnUrl . '#c' . $contentElement['uid'],
                            ],
                        )->__toString(),
                    ],
                ],
            ];
        }

        GeneralUtility::makeInstance(EventDispatcher::class)->dispatch(new FrontendEditDropdownModifyEvent($result));

        return new JsonResponse($result);
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
}
