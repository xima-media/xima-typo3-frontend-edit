<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Event;

class FrontendEditDropdownModifyEvent
{
    final public const NAME = 'xima_typo3_frontend_edit.frontend_edit.dropdown.modify';

    public function __construct(
        protected array $dropdownData
    ) {
    }

    public function getDropdownData(): array
    {
        return $this->dropdownData;
    }

    public function setDropdownData(array $dropdownData): void
    {
        $this->dropdownData = $dropdownData;
    }
}
