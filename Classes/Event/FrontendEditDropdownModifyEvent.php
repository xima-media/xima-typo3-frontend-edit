<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Event;

use Xima\XimaTypo3FrontendEdit\Template\Component\Button;

class FrontendEditDropdownModifyEvent
{
    final public const NAME = 'xima_typo3_frontend_edit.frontend_edit.dropdown.modify';

    public function __construct(
        protected array $contentElement,
        protected Button $menuButton,
        protected string $returnUrl
    ) {
    }

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
