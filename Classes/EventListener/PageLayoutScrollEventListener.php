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

use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;

/**
 * PageLayoutScrollEventListener.
 *
 * @see https://forge.typo3.org/issues/89678 - Related TYPO3 Core issue
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
#[AsEventListener(identifier: 'xima-typo3-frontend-edit/backend/page-layout-scroll')]
final readonly class PageLayoutScrollEventListener
{
    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (null === $request) {
            return;
        }

        $queryParams = $request->getQueryParams();

        // Only load on Page Layout module when scrollToElement is present
        if (!array_key_exists('scrollToElement', $queryParams)) {
            return;
        }

        // Verify we're on the web_layout route
        $route = $request->getAttribute('route');
        if (null === $route || 'web_layout' !== $route->getOption('_identifier')) {
            return;
        }

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@xima/ximatypo3frontendedit/scroll_to_element.js');
    }
}
