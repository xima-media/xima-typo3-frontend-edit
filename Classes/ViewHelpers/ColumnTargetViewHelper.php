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

namespace Xima\XimaTypo3FrontendEdit\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Xima\XimaTypo3FrontendEdit\Service\Authentication\BackendUserService;

use function sprintf;

/**
 * Renders a lightweight DOM marker for empty column detection.
 *
 * Outputs a hidden `<div>` with `data-xfe-colpos` (and optionally
 * `data-xfe-container`) attributes. The actual "Create new content"
 * button is injected client-side by frontend_edit.js after it receives
 * empty-column data from the AJAX endpoint.
 *
 * Only rendered when a backend user is logged in and frontend editing
 * is allowed and not disabled.
 *
 * Usage:
 *
 *     {namespace xfe=Xima\XimaTypo3FrontendEdit\ViewHelpers}
 *
 *     <!-- Page column marker -->
 *     <xfe:columnTarget colPos="0" />
 *
 *     <!-- Container column marker -->
 *     <xfe:columnTarget colPos="201" containerUid="{data.uid}" />
 *
 * @license GPL-2.0-or-later
 */
class ColumnTargetViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly BackendUserService $backendUserService,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('colPos', 'int', 'The column position (colPos) from the backend layout', true);
        $this->registerArgument('containerUid', 'int', 'The UID of the parent container element (for container columns)', false);
    }

    public function render(): string
    {
        if (!$this->backendUserService->isFrontendEditAllowed()
            || $this->backendUserService->isFrontendEditDisabled()
        ) {
            return '';
        }

        $colPos = (int) $this->arguments['colPos'];
        $containerUid = $this->arguments['containerUid'] ?? null;

        $containerAttr = null !== $containerUid
            ? sprintf(' data-xfe-container="%d"', (int) $containerUid)
            : '';

        return sprintf(
            '<div data-xfe-colpos="%d"%s hidden></div>',
            $colPos,
            $containerAttr,
        );
    }
}
