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

use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};

use function array_map;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
use function sprintf;

/**
 * ContainerContextResolver.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class ContainerContextResolver
{
    public function __construct(
        private Registry $registry,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Validate a drop target and resolve it to a column context.
     *
     * A null $containerUid means the target is a page column; any other value
     * must pass all container rules before it is trusted.
     *
     * @param array<string, mixed> $movedRecord
     *
     * @return array{valid: bool, colPos: int, containerUid: int, error: string|null}
     */
    public function resolve(?int $containerUid, int $colPos, array $movedRecord): array
    {
        $movedCType = (string) ($movedRecord['CType'] ?? '');
        $pid = (int) ($movedRecord['pid'] ?? 0);

        if (null === $containerUid || 0 === $containerUid) {
            // Rule 6: a container column number without a container would orphan
            // the record — colPos set, tx_container_parent 0.
            if (in_array($colPos, $this->allRegisteredContainerColPos(), true)) {
                return $this->reject(sprintf('column %d is a container column but no container was given', $colPos));
            }

            return $this->accept($colPos, 0);
        }

        // Rule 5: a container element itself must not be nested via drag & drop.
        if ($this->registry->isContainerElement($movedCType)) {
            return $this->reject('a container element cannot be dropped into a container');
        }

        // Rule 1: the container must exist and live on the same page.
        $container = $this->fetchRecord($containerUid);
        if (null === $container) {
            return $this->reject(sprintf('container %d not found', $containerUid));
        }
        if ((int) $container['pid'] !== $pid) {
            return $this->reject(sprintf('container %d is on a different page', $containerUid));
        }

        // Rule 2: the target must actually be a container element.
        $containerCType = (string) $container['CType'];
        if (!$this->registry->isContainerElement($containerCType)) {
            return $this->reject(sprintf('record %d is not a container element', $containerUid));
        }

        // Rule 3: the column must belong to THIS container.
        $available = array_map(intval(...), $this->registry->getAllAvailableColumnsColPos($containerCType));
        if (!in_array($colPos, $available, true)) {
            return $this->reject(sprintf('column %d is not registered for container %d', $colPos, $containerUid));
        }

        // Rule 4: registry-based CType restriction — works without EXT:content_defender.
        if (!$this->registry->isAllowedInColumn($movedCType, $colPos, $containerCType)) {
            return $this->reject(sprintf('content type %s is not allowed in column %d', $movedCType, $colPos));
        }

        return $this->accept($colPos, $containerUid);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchRecord(int $uid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $record = $queryBuilder
            ->select('uid', 'pid', 'CType', 'deleted')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();

        return is_array($record) ? $record : null;
    }

    /**
     * @return array{valid: bool, colPos: int, containerUid: int, error: string|null}
     */
    private function accept(int $colPos, int $containerUid): array
    {
        return ['valid' => true, 'colPos' => $colPos, 'containerUid' => $containerUid, 'error' => null];
    }

    /**
     * @return array{valid: bool, colPos: int, containerUid: int, error: string|null}
     */
    private function reject(string $error): array
    {
        return ['valid' => false, 'colPos' => 0, 'containerUid' => 0, 'error' => $error];
    }

    /**
     * @return list<int>
     */
    private function allRegisteredContainerColPos(): array
    {
        $colPositions = [];
        foreach ($this->registry->getRegisteredCTypes() as $cType) {
            foreach ($this->registry->getAllAvailableColumnsColPos($cType) as $colPos) {
                $colPositions[] = (int) $colPos;
            }
        }

        return array_values(array_unique($colPositions));
    }
}
