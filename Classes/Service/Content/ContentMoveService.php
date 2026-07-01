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
    ) {}

    /**
     * Whether a record is within the MVP scope for frontend drag & drop.
     *
     * Container children (EXT:container) and translated elements are out of scope
     * and keep the classic backend move button as fallback.
     *
     * @param array<string, mixed> $record
     */
    public function isMovable(array $record): bool
    {
        if ((int) ($record['tx_container_parent'] ?? 0) > 0) {
            return false;
        }

        return (int) ($record['sys_language_uid'] ?? 0) <= 0;
    }

    /**
     * Move a content element via the core DataHandler.
     *
     * The target page is always derived from the record itself, so a crafted
     * request cannot move an element across pages (out of MVP scope). When a
     * neighbour is given it must live on the same page.
     *
     * @return array{success: bool, statusCode: int, errors: list<string>}
     */
    public function move(int $uid, int $targetColPos, ?int $targetUid): array
    {
        // Fetch all fields: tx_container_parent only exists when EXT:container is
        // installed, so it must not be named explicitly in the SELECT.
        $record = BackendUtility::getRecord('tt_content', $uid);
        if (null === $record) {
            return $this->failure(400, sprintf('Content element %d not found', $uid));
        }

        if (!$this->isMovable($record)) {
            return $this->failure(422, 'This content element cannot be moved via drag & drop (use the backend move dialog instead)');
        }

        if (!$this->backendUserService->hasRecordEditAccess('tt_content', $record)) {
            return $this->failure(403, 'You are not allowed to edit this content element');
        }

        $pid = (int) $record['pid'];

        if (null !== $targetUid && $targetUid > 0) {
            $neighbour = BackendUtility::getRecord('tt_content', $targetUid);
            if (null === $neighbour || (int) $neighbour['pid'] !== $pid || !$this->isMovable($neighbour)) {
                return $this->failure(422, 'Invalid drop target');
            }
        }

        $command = $this->buildMoveCommand($uid, $targetColPos, $targetUid, $pid);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $command, $this->backendUserService->getBackendUser());
        $dataHandler->process_cmdmap();

        if ([] !== $dataHandler->errorLog) {
            return $this->failure(400, ...array_map(strval(...), array_values($dataHandler->errorLog)));
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
     * @return array<string, array<int, array{move: array{action: string, target: int, update: array{colPos: int}}}>>
     */
    public function buildMoveCommand(int $uid, int $targetColPos, ?int $targetUid, int $pid): array
    {
        $target = null !== $targetUid && $targetUid > 0 ? -$targetUid : $pid;

        return [
            'tt_content' => [
                $uid => [
                    'move' => [
                        'action' => 'paste',
                        'target' => $target,
                        'update' => ['colPos' => $targetColPos],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{success: bool, statusCode: int, errors: list<string>}
     */
    private function failure(int $statusCode, string ...$errors): array
    {
        return ['success' => false, 'statusCode' => $statusCode, 'errors' => array_values($errors)];
    }
}
