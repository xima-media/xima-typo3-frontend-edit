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

namespace Xima\XimaTypo3FrontendEdit\Tests\Functional\Middleware;

use ArrayObject;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

use function sprintf;

/**
 * RequestMiddlewaresTest.
 *
 * TYPO3 silently ignores a before/after reference to a middleware that does not
 * exist, so a key removed in a newer core drops the ordering constraint without
 * any error.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
final class RequestMiddlewaresTest extends FunctionalTestCase
{
    private const IDENTIFIER = 'xima/frontend-edit-tool';

    protected array $testExtensionsToLoad = [
        'xima/xima-typo3-frontend-edit',
    ];

    #[Test]
    public function referencedMiddlewaresExistInTheFrontendStack(): void
    {
        $stack = array_keys($this->resolveFrontendStack());

        foreach (['before', 'after'] as $direction) {
            foreach ($this->definition()[$direction] as $reference) {
                self::assertContains($reference, $stack, sprintf('"%s" constraint "%s" is not a frontend middleware in this core', $direction, $reference));
            }
        }
    }

    #[Test]
    public function middlewareRunsAfterTheTypoScriptFrontendPreparation(): void
    {
        $stack = array_keys($this->resolveFrontendStack());

        // The resolved stack is reversed: the last entry is dispatched first.
        self::assertLessThan(
            array_search('typo3/cms-frontend/prepare-tsfe-rendering', $stack, true),
            array_search(self::IDENTIFIER, $stack, true),
        );
        self::assertGreaterThan(
            array_search('typo3/cms-frontend/content-length-headers', $stack, true),
            array_search(self::IDENTIFIER, $stack, true),
        );
    }

    /**
     * @return array<string, class-string>
     */
    private function resolveFrontendStack(): array
    {
        $stack = $this->get(MiddlewareStackResolver::class)->resolve('frontend');

        // v13 returns a plain array, v14 an ArrayObject.
        return $stack instanceof ArrayObject ? $stack->getArrayCopy() : $stack;
    }

    /**
     * @return array{before: list<string>, after: list<string>}
     */
    private function definition(): array
    {
        $configuration = require __DIR__.'/../../../Configuration/RequestMiddlewares.php';

        return $configuration['frontend'][self::IDENTIFIER];
    }
}
