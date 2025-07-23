<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "xima_typo3_frontend_edit".
 *
 * Copyright (C) 2024-2025 Konrad Michalik <hej@konradmichalik.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Xima\XimaTypo3FrontendEdit\Event;

use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

class FrontendEditDropdownModifyEvent
{
    final public const NAME = 'xima_typo3_frontend_edit.frontend_edit.dropdown.modify';

    public function __construct(
        protected array $contentElement,
        protected Button $menuButton,
        protected string $returnUrl
    ) {}

    public function getContentElement(): array
    {
        return $this->contentElement;
    }

    /**
    * @return Button
    */
    public function getMenuButton(): Button
    {
        return $this->menuButton;
    }

    /**
    * @param Button $menuButton
    */
    public function setMenuButton(Button $menuButton): void
    {
        $this->menuButton = $menuButton;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }
}
