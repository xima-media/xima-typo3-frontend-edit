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
 * FrontendEditDropdownModifyEvent.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0-or-later
 */
class FrontendEditDropdownModifyEvent
{
    final public const NAME = 'xima_typo3_frontend_edit.frontend_edit.dropdown.modify';

    public function __construct(
        /** @var array<string, mixed> */
        protected array $contentElement,
        protected Button $menuButton,
        protected string $returnUrl,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getContentElement(): array
    {
        return $this->contentElement;
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
