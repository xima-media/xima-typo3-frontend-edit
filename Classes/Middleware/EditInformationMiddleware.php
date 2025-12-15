<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\MenuGenerator;
use Xima\XimaTypo3FrontendEdit\Traits\ExtensionConfigurationTrait;
use Xima\XimaTypo3FrontendEdit\Utility\UrlUtility;

use function is_array;
use function is_scalar;

/**
 * EditInformationMiddleware.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class EditInformationMiddleware implements MiddlewareInterface
{
    use ExtensionConfigurationTrait;

    public function __construct(
        protected readonly MenuGenerator $menuGenerator,
        protected readonly BackendUserService $backendUserService,
        protected readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * @throws UnableToLinkException
     * @throws JsonException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);
        $params = $request->getQueryParams();

        if (!isset($params['type']) || Configuration::TYPE !== $params['type']) {
            return $response;
        }

        $routing = $request->getAttribute('routing');
        $pid = $routing instanceof \TYPO3\CMS\Core\Routing\PageArguments ? $routing->getPageId() : 0;
        $language = $request->getAttribute('language');
        $languageUid = $language instanceof \TYPO3\CMS\Core\Site\Entity\SiteLanguage ? $language->getLanguageId() : 0;

        if (!$this->backendUserService->hasPageAccess($pid)) {
            return new JsonResponse([]);
        }

        $returnUrl = $this->getReturnUrl($request, $pid, $languageUid);

        try {
            $data = $this->getRequestData($request);
        } catch (InvalidArgumentException) {
            return new JsonResponse(['error' => 'Invalid request data'], 400);
        }

        $dropdown = $this->menuGenerator->getDropdown($pid, $returnUrl, $languageUid, $data);

        return new JsonResponse(mb_convert_encoding($dropdown, 'UTF-8'));
    }

    /**
     * @throws UnableToLinkException
     */
    private function getReturnUrl(ServerRequestInterface $request, int $pid, int $languageUid): string
    {
        $referer = $request->getHeaderLine('Referer');

        if ('' === $referer || $this->shouldForceReturnUrlGeneration()) {
            return UrlUtility::getUrl($pid, $languageUid);
        }

        return $referer;
    }

    private function validateContentType(ServerRequestInterface $request): void
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if ('' !== $contentType && !str_starts_with($contentType, 'application/json')) {
            throw new InvalidArgumentException('Invalid Content-Type header. Expected application/json', 1640000021);
        }
    }

    /**
     * @return array<mixed>
     */
    private function validateJsonStructure(mixed $decoded): array
    {
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Invalid JSON structure. Expected object/array', 1640000022);
        }

        foreach ($decoded as $value) {
            if (!is_array($value) && !is_scalar($value) && null !== $value) {
                throw new InvalidArgumentException('Invalid JSON value type', 1640000024);
            }
        }

        return $decoded;
    }

    /**
     * @return array<mixed>
     */
    private function getRequestData(ServerRequestInterface $request): array
    {
        $body = $request->getBody()->getContents();

        if ('' === $body) {
            return [];
        }

        $this->validateContentType($request);

        try {
            $decoded = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);

            return $this->validateJsonStructure($decoded);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON format: '.$e->getMessage(), 1640000025, $e);
        }
    }
}
