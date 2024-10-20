<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\MenuGenerator;

class EditInformationMiddleware implements MiddlewareInterface
{
    public function __construct(protected readonly MenuGenerator $menuGenerator)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $params = $request->getQueryParams();

        if (
            !($response instanceof NullResponse)
            && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && isset($params['type'])
            && $params['type'] === Configuration::TYPE
        ) {
            $pid = $request->getAttribute('routing')->getPageId();
            $returnUrl = $request->getHeaderLine('Referer');
            $languageUid = $request->getQueryParams()['language_uid'] ?? 0;

            $data = json_decode($request->getBody()->getContents(), true) ?? [];

            if (!$this->checkBackendUserPageAccess((int)$pid)) {
                return new JsonResponse([]);
            }

            return new JsonResponse($this->menuGenerator->getDropdown((int)$pid, $returnUrl, (int)$languageUid, $data));
        }

        return $response;
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
