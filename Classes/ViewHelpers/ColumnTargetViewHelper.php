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
 * ColumnTargetViewHelper.
 *
 * @license GPL-2.0-or-later
 * @author Konrad Michalik <hej@konradmichalik.dev>
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
