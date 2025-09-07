<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "xima_typo3_frontend_edit".
 *
 * Copyright (C) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\MenuGenerator;
use Xima\XimaTypo3FrontendEdit\Traits\ExtensionConfigurationTrait;
use Xima\XimaTypo3FrontendEdit\Utility\UrlUtility;

/**
 * EditInformationMiddleware.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
class EditInformationMiddleware implements MiddlewareInterface
{
    use ExtensionConfigurationTrait;

    public function __construct(
        protected readonly MenuGenerator $menuGenerator,
        protected readonly BackendUserService $backendUserService,
        protected readonly ExtensionConfiguration $extensionConfiguration
    ) {}

    /**
    * @throws UnableToLinkException
    * @throws \JsonException
    */
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

        try {
            $data = $this->getRequestData($request);
        } catch (\InvalidArgumentException $e) {
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

        if ($referer === '' || $this->shouldForceReturnUrlGeneration()) {
            return UrlUtility::getUrl($pid, $languageUid);
        }

        return $referer;
    }

    private function validateContentType(ServerRequestInterface $request): void
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if ($contentType !== '' && !str_starts_with($contentType, 'application/json')) {
            throw new \InvalidArgumentException(
                'Invalid Content-Type header. Expected application/json',
                1640000021
            );
        }
    }

    private function validateJsonStructure(mixed $decoded): array
    {
        if (!is_array($decoded)) {
            throw new \InvalidArgumentException(
                'Invalid JSON structure. Expected object/array',
                1640000022
            );
        }

        foreach ($decoded as $value) {
            if (!is_array($value) && !is_scalar($value) && $value !== null) {
                throw new \InvalidArgumentException(
                    'Invalid JSON value type',
                    1640000024
                );
            }
        }

        return $decoded;
    }

    private function getRequestData(ServerRequestInterface $request): array
    {
        $body = $request->getBody()->getContents();

        if ($body === '') {
            return [];
        }

        $this->validateContentType($request);

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return $this->validateJsonStructure($decoded);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(
                'Invalid JSON format: ' . $e->getMessage(),
                1640000025,
                $e
            );
        }
    }
}
