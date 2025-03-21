<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Xima\XimaTypo3FrontendEdit\Configuration;

class ResourceRenderer
{
    public function render(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:' . Configuration::EXT_KEY . '/Resources/Private/Templates/FrontendEdit.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:' . Configuration::EXT_KEY . '/Resources/Private/Layouts']);

        $view->assign('resources', ResourceUtility::getResources(['nonce' => $this->resolveNonceValue()]));
        return $view->render();
    }

    private function resolveNonceValue(): string
    {
        return property_exists(RequestId::class, 'nonce') ? GeneralUtility::makeInstance(RequestId::class)->nonce->consume() : '';
    }
}
