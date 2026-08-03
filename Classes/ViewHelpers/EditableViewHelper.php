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

use function is_array;
use function preg_match;
use function sprintf;

/**
 * EditableViewHelper.
 *
 * Renders the `data-frontend-edit="{table}:{uid}"` attribute - the
 * alternative to the `id="c{uid}"` anchor pattern for templates that cannot
 * carry that anchor (e.g. DCE, other custom Fluid templates), or for editing
 * a foreign record (news, addresses, ...) via a thin edit+info+history menu
 * (see #216). Place directly inside the opening tag of the record's own
 * wrapping element; it renders only the attribute, not a wrapping tag.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class EditableViewHelper extends AbstractViewHelper
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
        $this->registerArgument('record', 'array', 'The record (or any array containing at least "uid")');
        $this->registerArgument('uid', 'int', 'Record uid, alternative to "record"');
        $this->registerArgument('table', 'string', 'Database table of the record', false, 'tt_content');
    }

    public function render(): string
    {
        if (!$this->backendUserService->isFrontendEditAllowed()
            || $this->backendUserService->isFrontendEditDisabled()
        ) {
            return '';
        }

        $uid = $this->resolveUid();
        if (null === $uid || $uid <= 0) {
            return '';
        }

        $table = (string) $this->arguments['table'];
        if (1 !== preg_match('/^[a-z][a-z0-9_]*$/', $table)) {
            return '';
        }

        return sprintf(' data-frontend-edit="%s:%d"', $table, $uid);
    }

    private function resolveUid(): ?int
    {
        if (null !== $this->arguments['uid']) {
            return (int) $this->arguments['uid'];
        }

        $record = $this->arguments['record'] ?? null;
        if (is_array($record) && isset($record['uid'])) {
            return (int) $record['uid'];
        }

        return null;
    }
}
