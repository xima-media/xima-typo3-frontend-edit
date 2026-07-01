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
 * MoveResult.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final readonly class MoveResult
{
    /**
     * @param list<string> $errors
     */
    private function __construct(
        public bool $success,
        public int $statusCode,
        public array $errors = [],
    ) {}

    public static function success(): self
    {
        return new self(true, 200);
    }

    public static function forbidden(string $error): self
    {
        return new self(false, 403, [$error]);
    }

    /**
     * Out of MVP scope (e.g. container child, translated element).
     */
    public static function unprocessable(string $error): self
    {
        return new self(false, 422, [$error]);
    }

    /**
     * @param list<string> $errors
     */
    public static function failed(array $errors): self
    {
        return new self(false, 400, $errors);
    }
}
