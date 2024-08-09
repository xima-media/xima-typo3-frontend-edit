<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use Xima\XimaTypo3FrontendEdit\Service\MenuGenerator;

#[AsController]
final class EditController
{
    public function __construct(protected readonly MenuGenerator $menuGenerator)
    {
    }

    public function getContentElementsByPage(ServerRequestInterface $request): ResponseInterface
    {
        $pid = $request->getQueryParams()['pid']
            ?? throw new \InvalidArgumentException(
                'Please provide pid',
                1722599959,
            );
        $returnUrl = $request->getQueryParams()['returnUrl'] ? strtok(urldecode($request->getQueryParams()['returnUrl']), '#') : '';
        $languageUid = $request->getQueryParams()['language_uid'] ?? 0;

        if (!$this->checkBackendUserPageAccess((int)$pid)) {
            return new JsonResponse([]);
        }

        return new JsonResponse($this->menuGenerator->getDropdown((int)$pid, $returnUrl, (int)$languageUid));
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
