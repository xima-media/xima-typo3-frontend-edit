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
use Xima\XimaTypo3FrontendEdit\Service\Content\{ContentMoveService, EmptyColumnService};
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
        private EmptyColumnService $emptyColumnService,
        private ContentMoveService $contentMoveService,
    ) {}

    /**
     * Toggle frontend edit active state.
     *
     * Uses the backend user's uc (user configuration) to persist the state.
     */
    public function toggleAction(): JsonResponse
    {
        if (!$this->backendUserService->isFrontendEditAllowed()) {
            return new JsonResponse(['success' => false, 'error' => 'Frontend edit is not allowed'], 403);
        }

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
        if (!$this->backendUserService->isFrontendEditAllowed()) {
            return new JsonResponse([]);
        }

        $backendUser = $this->backendUserService->getBackendUser();
        if (null === $backendUser || null === $backendUser->user) {
            return new JsonResponse([]);
        }

        $params = $request->getQueryParams();

        $pidParam = $params['pid'] ?? null;
        $pid = filter_var($pidParam, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (false === $pid) {
            return new JsonResponse(['error' => 'Missing or invalid parameter: pid must be a positive integer'], 400);
        }

        $languageParam = $params['language'] ?? 0;
        $languageUid = filter_var($languageParam, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if (false === $languageUid) {
            return new JsonResponse(['error' => 'Invalid parameter: language must be a non-negative integer'], 400);
        }

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

        $columnTargets = $this->emptyColumnService->getColumnTargets($pid, $languageUid, $returnUrl, $data);

        return new JsonResponse(mb_convert_encoding([
            'contentElements' => $dropdown,
            'columnTargets' => $columnTargets,
        ], 'UTF-8'));
    }

    /**
     * Move a content element to a new position (frontend drag & drop).
     *
     * Expects a JSON body with:
     * - uid: content element to move (required, positive int)
     * - targetColPos: destination column (required, non-negative int)
     * - targetUid: neighbour to insert after; 0/omitted = top of column (optional int)
     * - language: current frontend language (must be 0 — translations are out of scope)
     */
    public function moveAction(ServerRequestInterface $request): JsonResponse
    {
        if (!$this->backendUserService->isFrontendEditAllowed()) {
            return new JsonResponse(['success' => false, 'error' => 'Frontend edit is not allowed'], 403);
        }

        try {
            $data = $this->getRequestData($request);
        } catch (InvalidArgumentException) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid request data'], 400);
        }

        $uid = filter_var($data['uid'] ?? null, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (false === $uid) {
            return new JsonResponse(['success' => false, 'error' => 'Missing or invalid parameter: uid'], 400);
        }

        $targetColPos = filter_var($data['targetColPos'] ?? null, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if (false === $targetColPos) {
            return new JsonResponse(['success' => false, 'error' => 'Missing or invalid parameter: targetColPos'], 400);
        }

        $language = filter_var($data['language'] ?? 0, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if (false === $language) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid parameter: language'], 400);
        }
        if ($language > 0) {
            return new JsonResponse(['success' => false, 'error' => 'Translated content cannot be moved via drag & drop'], 422);
        }

        $targetUidValue = $data['targetUid'] ?? null;
        $targetUid = null === $targetUidValue ? null : filter_var($targetUidValue, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if (false === $targetUid) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid parameter: targetUid'], 400);
        }

        $result = $this->contentMoveService->move($uid, $targetColPos, $targetUid);

        return new JsonResponse(
            $result['success']
                ? ['success' => true]
                : ['success' => false, 'errors' => $result['errors']],
            $result['statusCode'],
        );
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
