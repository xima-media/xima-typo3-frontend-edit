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

namespace Xima\XimaTypo3FrontendEdit\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsExecutedEvent;

use function sprintf;

/**
 * ContentElementMarkerEventListener.
 *
 * Wraps every rendered tt_content record in a pair of HTML comment markers, so the
 * frontend can identify content elements without relying on the id="c{uid}" anchor.
 *
 * Activation happens purely through TypoScript: the sentinel key is set inside a
 * condition (see Configuration/Sets/XimaTypo3FrontendEdit/setup.typoscript). This
 * listener must never check the backend user itself -- TypoScript condition verdicts
 * are part of the page cache identifier, PHP behaviour is not, so a check here would
 * serve marker-laden output to anonymous visitors from the shared page cache.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[AsEventListener(identifier: 'xima-typo3-frontend-edit/frontend/content-element-marker')]
final readonly class ContentElementMarkerEventListener
{
    /**
     * stdWrap key signalling that markers are wanted. Unknown to the Core, therefore
     * never executed as a stdWrap function -- it only carries the opt-in verdict.
     *
     * A dedicated key is required rather than writing markers via stdWrap.dataWrap:
     * wrap-type properties are single scalars, so a site package setting the same key
     * replaces ours without warning. bk2k/bootstrap-package does this through
     * lib.dynamicContent's renderObj.stdWrap.dataWrap. A separate key survives the
     * array_replace_recursive() inside ContentObjectRenderer::mergeTSRef().
     */
    private const SENTINEL_KEY = 'xfeMarkers';

    private const TABLE = 'tt_content';

    public function __invoke(AfterStdWrapFunctionsExecutedEvent $event): void
    {
        if (!($event->getConfiguration()[self::SENTINEL_KEY] ?? false)) {
            return;
        }

        $content = $event->getContent();
        if (null === $content || '' === $content) {
            return;
        }

        $uid = (int) ($event->getContentObjectRenderer()->data['uid'] ?? 0);
        if ($uid <= 0) {
            return;
        }

        // Casting to int keeps the payload numeric, so it can never contain "--" or
        // break the surrounding HTML comment.
        $event->setContent(sprintf(
            '<!--xfe:b:%1$s:%2$d-->%3$s<!--xfe:e:%1$s:%2$d-->',
            self::TABLE,
            $uid,
            $content,
        ));
    }
}
