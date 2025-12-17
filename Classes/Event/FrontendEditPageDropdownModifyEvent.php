<?php

declare(strict_types=1);

/*
 * This file is part of the "xima_typo3_frontend_edit" TYPO3 CMS extension.
 *
 * (c) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\XimaTypo3FrontendEdit\Event;

use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

/**
 * FrontendEditPageDropdownModifyEvent.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class FrontendEditPageDropdownModifyEvent
{
    final public const NAME = 'xima_typo3_frontend_edit.frontend_edit.page_dropdown.modify';

    public function __construct(
        protected int $pageId,
        protected int $languageUid,
        protected Button $menuButton,
        protected string $returnUrl,
    ) {}

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getLanguageUid(): int
    {
        return $this->languageUid;
    }

    public function getMenuButton(): Button
    {
        return $this->menuButton;
    }

    public function setMenuButton(Button $menuButton): void
    {
        $this->menuButton = $menuButton;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }
}
