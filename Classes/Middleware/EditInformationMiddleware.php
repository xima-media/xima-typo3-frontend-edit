<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\MenuGenerator;
use Xima\XimaTypo3FrontendEdit\Traits\ExtensionConfigurationTrait;
use Xima\XimaTypo3FrontendEdit\Utility\UrlUtility;

class EditInformationMiddleware implements MiddlewareInterface
{
    use ExtensionConfigurationTrait;

    public function __construct(
        protected readonly MenuGenerator $menuGenerator,
        protected readonly BackendUserService $backendUserService,
        protected readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        $params = $request->getQueryParams();

        if (!isset($params['type']) || $params['type'] !== Configuration::TYPE) {
            return $response;
        }

        $pid = $request->getAttribute('routing')->getPageId();
        $languageUid = $request->getAttribute('language')->getLanguageId();

        if (!$this->backendUserService->hasPageAccess($pid)) {
            return new JsonResponse([]);
        }

        $returnUrl = $this->getReturnUrl($request, $pid, $languageUid);
        $data = $this->getRequestData($request);

        $dropdown = $this->menuGenerator->getDropdown($pid, $returnUrl, $languageUid, $data);

        return new JsonResponse(mb_convert_encoding($dropdown, 'UTF-8'));
    }

    private function getReturnUrl(ServerRequestInterface $request, int $pid, int $languageUid): string
    {
        $referer = $request->getHeaderLine('Referer');

        if ($referer === '' || $this->shouldForceReturnUrlGeneration()) {
            return UrlUtility::getUrl($pid, $languageUid);
        }

        return $referer;
    }

    private function getRequestData(ServerRequestInterface $request): array
    {
        $body = $request->getBody()->getContents();

        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
