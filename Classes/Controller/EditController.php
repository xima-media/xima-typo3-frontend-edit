<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Xima\XimaTypo3FrontendEdit\Service\MenuGenerator;

#[AsController]
final class EditController extends ActionController
{
    public function __construct(protected readonly MenuGenerator $menuGenerator)
    {
    }

    public function contentElementsAction(): ResponseInterface
    {
        $pid = $GLOBALS['TSFE']->id;
        $returnUrl = $this->request->getHeaderLine('Referer');
        $languageUid = $this->request->getQueryParams()['language_uid'] ?? 0;

        $data = json_decode($this->request->getBody()->getContents(), true) ?? [];

        if (!$this->checkBackendUserPageAccess((int)$pid)) {
            return new JsonResponse([]);
        }

        return new JsonResponse($this->menuGenerator->getDropdown((int)$pid, $returnUrl, (int)$languageUid, $data));
    }

    private function checkBackendUserPageAccess(int $pid): bool
    {
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
            return false;
        }
        return true;
    }
}
