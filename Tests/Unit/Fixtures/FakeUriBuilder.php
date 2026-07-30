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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Fixtures;

use Psr\Http\Message\UriInterface;
use Throwable;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\Uri;

/**
 * FakeUriBuilder.
 *
 * Constructor-only stand-in for UriBuilder, for use with #[WithSingleton]: instantiable
 * without the real constructor's Router/FormProtectionFactory/RequestContextFactory dependencies.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class FakeUriBuilder extends UriBuilder
{
    public function __construct(
        private readonly UriInterface|Throwable $result = new Uri('/typo3/mock'),
    ) {}

    public function buildUriFromRoute($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): UriInterface
    {
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}
