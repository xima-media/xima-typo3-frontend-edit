<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Controller;

use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Utility\ResourceUtility;

#[AsController]
final class EditController extends ActionController
{
    public function __construct(private readonly RequestId $requestId)
    {
    }

    public function render(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:' . Configuration::EXT_KEY . '/Resources/Private/Templates/FrontendEdit.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Layouts']);

        $view->assign('resources', ResourceUtility::getResources(['nonce' => $this->requestId->nonce]));
        return $view->render();
    }
}
