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
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;
use Xima\XimaTypo3FrontendEdit\Service\Content\{ContentMoveService, EmptyColumnService};
use Xima\XimaTypo3FrontendEdit\Service\Menu\{ContentElementMenuGenerator, RecordMenuGenerator};
use Xima\XimaTypo3FrontendEdit\Service\Security\ReturnUrlValidator;
use Xima\XimaTypo3FrontendEdit\Service\Ui\IconDeduplicationService;

use function array_slice;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use function preg_match;

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
        private RecordMenuGenerator $recordMenuGenerator,
        private BackendUserService $backendUserService,
        private EmptyColumnService $emptyColumnService,
        private SettingsService $settingsService,
        private ContentMoveService $contentMoveService,
        private ReturnUrlValidator $returnUrlValidator,
        private IconDeduplicationService $iconDeduplicationService,
    ) {}

    /**
     * Toggle frontend edit active state.
     *
     * Uses the backend user's uc (user configuration) to persist the state.
     */
    public function toggleAction(ServerRequestInterface $request): JsonResponse
    {
        if (!$this->isSameOriginWriteRequest($request)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid request'], 403);
        }

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
        if (!$this->returnUrlValidator->isValid($returnUrl, $request, $pid)) {
            return new JsonResponse(['error' => 'Invalid parameter: returnUrl host not allowed'], 400);
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
        $deduplicated = $this->iconDeduplicationService->deduplicate($dropdown);

        $columnTargets = $this->emptyColumnService->getColumnTargets(
            $pid,
            $languageUid,
            $returnUrl,
            $data,
            $this->settingsService->isShowInsertButtons($request),
        );

        $recordReferences = $this->extractValidatedRecordReferences($data);
        $records = [] !== $recordReferences
            ? $this->recordMenuGenerator->getDropdown($recordReferences, $languageUid, $returnUrl)
            : [];

        return new JsonResponse([
            'contentElements' => $deduplicated['contentElements'],
            'columnTargets' => $columnTargets,
            'records' => $records,
            'icons' => $deduplicated['icons'],
        ]);
    }

    /**
     * Move a content element to a new position (frontend drag & drop).
     *
     * Expects a JSON body with:
     * - uid: content element to move (required, positive int)
     * - targetColPos: destination column (required, non-negative int)
     * - targetUid: neighbour to insert after; 0/omitted = top of column (optional int)
     * - language: current frontend language (must be 0 — translations are out of scope)
     * - targetContainerUid: EXT:container element the target column belongs to
     *   (optional; omitted or null means the target is a page column)
     */
    public function moveAction(ServerRequestInterface $request): JsonResponse
    {
        if (!$this->isSameOriginWriteRequest($request)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid request'], 403);
        }

        if (!$this->backendUserService->isFrontendEditAllowed()) {
            return new JsonResponse(['success' => false, 'error' => 'Frontend edit is not allowed'], 403);
        }

        try {
            $data = $this->getRequestData($request);
        } catch (InvalidArgumentException) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid request data'], 400);
        }

        $parseResult = $this->parseMoveParameters($data);
        if ($parseResult['hasError']) {
            $statusCode = $parseResult['statusCode'] ?? 400;

            return new JsonResponse(['success' => false, 'error' => $parseResult['error']], $statusCode);
        }

        $result = $this->contentMoveService->move(
            $parseResult['uid'],
            $parseResult['targetColPos'],
            $parseResult['targetUid'],
            $parseResult['targetContainerUid'],
        );

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

    /**
     * Parses and validates `data-frontend-edit="{table}:{uid}"` references
     * collected client-side (DataService.collectDataItems() in
     * frontend_edit.js) for tables other than tt_content, which keeps using
     * the existing `_uids` path.
     *
     * @param array<int|string, mixed> $data
     *
     * @return array<int, array{table: string, uid: int}>
     */
    private function extractValidatedRecordReferences(array $data): array
    {
        if (!isset($data['_records']) || !is_array($data['_records'])) {
            return [];
        }

        $references = [];
        $seen = [];
        foreach ($data['_records'] as $entry) {
            if (!is_string($entry) || 1 !== preg_match('/^([a-z][a-z0-9_]*):([1-9]\d*)$/', $entry, $matches)) {
                continue;
            }

            $table = $matches[1];
            if ('tt_content' === $table || isset($seen[$entry])) {
                continue;
            }

            $seen[$entry] = true;
            $references[] = ['table' => $table, 'uid' => (int) $matches[2]];
        }

        // Limit to 100 references to prevent DoS attacks (mirrors extractValidatedUids's 500 cap).
        return array_slice($references, 0, 100);
    }

    /**
     * Guard state-changing requests against cross-site request forgery.
     *
     * Requires POST and, when the browser provides Fetch Metadata, a same-origin
     * context. Browsers that omit Sec-Fetch-Site (legacy) fall back to POST-only.
     */
    private function isSameOriginWriteRequest(ServerRequestInterface $request): bool
    {
        if ('POST' !== $request->getMethod()) {
            return false;
        }

        $fetchSite = $request->getHeaderLine('Sec-Fetch-Site');

        return '' === $fetchSite
            || in_array($fetchSite, ['same-origin', 'same-site', 'none'], true);
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

    /**
     * Parse move request parameters.
     *
     * @param array<mixed> $data
     *
     * @return array{hasError: true, statusCode?: int, error: string}|array{hasError: false, uid: int, targetColPos: int, targetUid: int|null, targetContainerUid: int|null}
     */
    private function parseMoveParameters(array $data): array
    {
        $uid = $this->parseInt($data, 'uid', 1);
        if (false === $uid) {
            return ['hasError' => true, 'error' => 'Missing or invalid parameter: uid'];
        }

        $targetColPos = $this->parseInt($data, 'targetColPos', 0);
        if (false === $targetColPos) {
            return ['hasError' => true, 'error' => 'Missing or invalid parameter: targetColPos'];
        }

        $language = $this->parseInt($data, 'language', 0, 0);
        if (false === $language) {
            return ['hasError' => true, 'error' => 'Invalid parameter: language'];
        }
        if ($language > 0) {
            return ['hasError' => true, 'statusCode' => 422, 'error' => 'Translated content cannot be moved via drag & drop'];
        }

        $targetUid = $this->parseOptionalInt($data, 'targetUid', 0);
        if (false === $targetUid) {
            return ['hasError' => true, 'error' => 'Invalid parameter: targetUid'];
        }

        $targetContainerUid = $this->parseOptionalInt($data, 'targetContainerUid', 1);
        if (false === $targetContainerUid) {
            return ['hasError' => true, 'error' => 'Invalid parameter: targetContainerUid'];
        }

        return [
            'hasError' => false,
            'uid' => $uid,
            'targetColPos' => $targetColPos,
            'targetUid' => $targetUid,
            'targetContainerUid' => $targetContainerUid,
        ];
    }

    /**
     * Parse a required integer parameter, substituting $default when absent.
     *
     * @param array<mixed> $data
     */
    private function parseInt(array $data, string $key, int $min, ?int $default = null): int|false
    {
        return filter_var($data[$key] ?? $default, \FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]);
    }

    /**
     * Parse an optional integer parameter: absent or explicit null passes
     * through as null, present-but-invalid yields false.
     *
     * @param array<mixed> $data
     */
    private function parseOptionalInt(array $data, string $key, int $min): int|false|null
    {
        $value = $data[$key] ?? null;

        return null === $value ? null : filter_var($value, \FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]);
    }
}
