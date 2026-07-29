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

namespace Xima\XimaTypo3FrontendEdit\Service\Content;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;
use Xima\XimaTypo3FrontendEdit\Service\Configuration\SettingsService;

use function array_map;
use function array_values;
use function sprintf;

/**
 * ContentMoveService.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class ContentMoveService
{
    public function __construct(
        private BackendUserService $backendUserService,
        private SettingsService $settingsService,
        private ContainerContextResolver $containerContextResolver,
    ) {}

    /**
     * Whether a record is within scope for frontend drag & drop.
     *
     * Translated elements follow their parent's ordering, so reordering them
     * would have no meaningful effect. Container children are in scope; their
     * target is validated by ContainerContextResolver.
     *
     * @param array<string, mixed> $record
     */
    public function isMovable(array $record): bool
    {
        return (int) ($record['sys_language_uid'] ?? 0) <= 0;
    }

    /**
     * Move a content element via the core DataHandler.
     *
     * The target page is always derived from the record itself, so a crafted
     * request cannot move an element across pages (out of MVP scope). When a
     * neighbour is given it must live on the same page and in the resolved
     * column context.
     *
     * @return array{success: bool, statusCode: int, errors: list<string>}
     */
    public function move(int $uid, int $targetColPos, ?int $targetUid, ?int $targetContainerUid = null): array
    {
        // Fetch all fields: tx_container_parent only exists when EXT:container is
        // installed, so it must not be named explicitly in the SELECT.
        $record = BackendUtility::getRecord('tt_content', $uid);
        if (null === $record) {
            return $this->failure(400, sprintf('Content element %d not found', $uid));
        }

        $pid = (int) $record['pid'];

        // Enforce the site-level flag here (not in the controller): backend AJAX
        // routes have no "site" request attribute, so the setting is resolved via
        // the record's page instead.
        if (!$this->settingsService->isDragAndDropEnabledForPage($pid)) {
            return $this->failure(403, 'Drag & drop reordering is disabled for this site');
        }

        if (!$this->isMovable($record)) {
            return $this->failure(422, 'This content element cannot be moved via drag & drop (use the backend move dialog instead)');
        }

        if (!$this->backendUserService->hasRecordEditAccess('tt_content', $record)) {
            return $this->failure(403, 'You are not allowed to edit this content element');
        }

        $context = $this->containerContextResolver->resolve($targetContainerUid, $targetColPos, $record);
        if (!$context['valid']) {
            return $this->failure(422, (string) $context['error']);
        }

        if (null !== $targetUid && $targetUid > 0 && !$this->isValidNeighbour($targetUid, $pid, $context)) {
            return $this->failure(422, 'Invalid drop target');
        }

        $command = $this->buildMoveCommand($uid, $context['colPos'], $targetUid, $pid, $context['containerUid']);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $command, $this->backendUserService->getBackendUser());
        $dataHandler->process_cmdmap();

        if ([] !== $dataHandler->errorLog) {
            return $this->failure(400, ...array_map(strval(...), array_values($dataHandler->errorLog)));
        }

        // An empty errorLog is not proof the move happened: EXT:container hooks
        // rewrite and even unset commands. Confirm the record actually landed
        // where it was asked to go.
        $moved = BackendUtility::getRecord('tt_content', $uid);
        if (!$this->wasMovedAsRequested($moved, $context)) {
            return $this->failure(409, 'The move was not applied as requested');
        }

        return ['success' => true, 'statusCode' => 200, 'errors' => []];
    }

    /**
     * Build the core-compatible DataHandler command map for a move.
     *
     * Mirrors the backend page module: a negative target is "insert after this
     * record uid", a non-negative target is the page uid (insert as first element
     * in the target column). The colPos is applied via the move's update payload.
     *
     * tx_container_parent is ALWAYS part of the payload, including the value 0.
     * EXT:container forces it to 0 whenever colPos is set without it, which would
     * silently orphan a container child; and its own target rewriting for the top
     * of a container column only runs when both fields are present.
     *
     * @return array<string, array<int, array{move: array{action: string, target: int, update: array{colPos: int, tx_container_parent: int}}}>>
     */
    public function buildMoveCommand(
        int $uid,
        int $targetColPos,
        ?int $targetUid,
        int $pid,
        int $targetContainerUid = 0,
    ): array {
        $target = null !== $targetUid && $targetUid > 0 ? -$targetUid : $pid;

        return [
            'tt_content' => [
                $uid => [
                    'move' => [
                        'action' => 'paste',
                        'target' => $target,
                        'update' => [
                            'colPos' => $targetColPos,
                            'tx_container_parent' => $targetContainerUid,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array{valid: bool, colPos: int, containerUid: int, error: string|null} $context
     */
    private function isValidNeighbour(int $targetUid, int $pid, array $context): bool
    {
        $neighbour = BackendUtility::getRecord('tt_content', $targetUid);

        return null !== $neighbour
            && (int) $neighbour['pid'] === $pid
            && (int) $neighbour['colPos'] === $context['colPos']
            && (int) ($neighbour['tx_container_parent'] ?? 0) === $context['containerUid'];
    }

    /**
     * @param array<string, mixed>|null                                              $moved
     * @param array{valid: bool, colPos: int, containerUid: int, error: string|null} $context
     */
    private function wasMovedAsRequested(?array $moved, array $context): bool
    {
        return null !== $moved
            && (int) $moved['colPos'] === $context['colPos']
            && (int) ($moved['tx_container_parent'] ?? 0) === $context['containerUid'];
    }

    /**
     * @return array{success: bool, statusCode: int, errors: list<string>}
     */
    private function failure(int $statusCode, string ...$errors): array
    {
        return ['success' => false, 'statusCode' => $statusCode, 'errors' => array_values($errors)];
    }
}
