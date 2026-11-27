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

namespace Xima\XimaTypo3FrontendEdit\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use Xima\XimaTypo3FrontendEdit\Utility\ElementPayloadUtility;

/**
 * ElementPayloadUtilityTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[CoversClass(ElementPayloadUtility::class)]
final class ElementPayloadUtilityTest extends TestCase
{
    #[Test]
    public function restrictKeepsOnlyTheFieldsTheFrontendReads(): void
    {
        $element = [
            'uid' => 1,
            'pid' => 2,
            'header' => 'Title',
            'CType' => 'text',
            'colPos' => 0,
            'tx_container_parent' => 0,
            'l18n_parent' => 0,
            'l10n_source' => 0,
            'ctypeLabel' => 'Text',
            'ctypeIcon' => '<svg/>',
            '_table' => 'tt_content',
            '_ext' => ['vendor' => ['k' => 'v']],
            'newBeforeUrl' => '/before',
            'newAfterUrl' => '/after',
            'password' => 'hash',
            'mfa' => '{"totp":"secret"}',
            'bodytext' => 'body',
        ];

        $restricted = ElementPayloadUtility::restrict($element);

        self::assertSame(
            ['uid', 'pid', 'header', 'CType', 'colPos', 'tx_container_parent', 'l18n_parent', 'l10n_source', 'ctypeLabel', 'ctypeIcon', '_table', '_ext', 'newBeforeUrl', 'newAfterUrl'],
            array_keys($restricted),
        );
    }
}
