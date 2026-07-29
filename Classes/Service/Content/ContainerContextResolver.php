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

use function array_unique;
use function array_values;
use function in_array;
use function sprintf;

/**
 * ContainerContextResolver.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class ContainerContextResolver
{
    public function __construct(
        private Registry $registry,
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
        if (null === $containerUid || 0 === $containerUid) {
            return $this->accept($colPos, 0);
        }

        // Rule 5: a container element itself must not be nested via drag & drop.
        // Cycles are caught by EXT:container, but nesting is out of scope entirely.
        $movedCType = (string) ($movedRecord['CType'] ?? '');
        if ($this->registry->isContainerElement($movedCType)) {
            return $this->reject('a container element cannot be dropped into a container');
        }

        // Rule 3 (first stage): the column must belong to some registered
        // container. Task 3 narrows this to the specific target container.
        if (!in_array($colPos, $this->allRegisteredContainerColPos(), true)) {
            return $this->reject(sprintf('column %d is not a registered container column', $colPos));
        }

        return $this->accept($colPos, $containerUid);
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
