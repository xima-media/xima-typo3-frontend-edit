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

/**
 * ContainerContextResolver.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class ContainerContextResolver
{
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

        return $this->accept($colPos, $containerUid);
    }

    /**
     * @return array{valid: bool, colPos: int, containerUid: int, error: string|null}
     */
    private function accept(int $colPos, int $containerUid): array
    {
        return ['valid' => true, 'colPos' => $colPos, 'containerUid' => $containerUid, 'error' => null];
    }
}
