<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Controller;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Menu\ContentElementMenuGenerator;

use function is_array;
use function is_scalar;

/**
 * AjaxController.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[Autoconfigure(public: true)]
readonly class AjaxController
{
    public function __construct(
        private ContentElementMenuGenerator $contentElementMenuGenerator,
        private BackendUserService $backendUserService,
    ) {}

    /**
     * Toggle frontend edit active state.
     *
     * Uses the backend user's uc (user configuration) to persist the state.
     */
    public function toggleAction(): JsonResponse
    {
        $backendUser = $this->getBackendUser();

        if (null === $backendUser || null === $backendUser->user) {
            return new JsonResponse(['success' => false, 'error' => 'No backend user'], 403);
        }

        $currentValue = (bool) ($backendUser->uc[Configuration::UC_KEY_DISABLED] ?? false);
        $newValue = !$currentValue;

        // Update user configuration and persist
        $backendUser->uc[Configuration::UC_KEY_DISABLED] = $newValue;
        $backendUser->writeUC();

        return new JsonResponse([
            'success' => true,
            'disabled' => $newValue,
        ]);
    }

    /**
     * Get edit information for content elements on a page.
     *
     * Expects query parameters:
     * - pid: Page ID (required)
     * - language: Language UID (optional, defaults to 0)
     * - returnUrl: URL to return to after editing (required)
     *
     * Expects JSON body with additional data (optional).
     */
    public function editInformationAction(ServerRequestInterface $request): JsonResponse
    {
        $backendUser = $this->backendUserService->getBackendUser();
        if (null === $backendUser || null === $backendUser->user) {
            return new JsonResponse([]);
        }

        $params = $request->getQueryParams();

        $pid = (int) ($params['pid'] ?? 0);
        if (0 === $pid) {
            return new JsonResponse(['error' => 'Missing required parameter: pid'], 400);
        }

        $languageUid = (int) ($params['language'] ?? 0);
        $returnUrl = (string) ($params['returnUrl'] ?? '');
        if ('' === $returnUrl) {
            return new JsonResponse(['error' => 'Missing required parameter: returnUrl'], 400);
        }

        if (!$this->backendUserService->hasPageAccess($pid)) {
            return new JsonResponse([]);
        }

        try {
            $data = $this->getRequestData($request);
        } catch (InvalidArgumentException) {
            return new JsonResponse(['error' => 'Invalid request data'], 400);
        }

        $dropdown = $this->contentElementMenuGenerator->getDropdown($pid, $returnUrl, $languageUid, $request, $data);

        return new JsonResponse(mb_convert_encoding($dropdown, 'UTF-8'));
    }

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
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
