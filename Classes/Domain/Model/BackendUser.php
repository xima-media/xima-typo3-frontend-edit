<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Domain\Model;

use TYPO3\CMS\Beuser\Domain\Model\BackendUser as User;

class BackendUser extends User
{
    protected bool $disable = false;

    public function isDisable(): bool
    {
        return $this->disable;
    }

    public function setDisable(bool $disable): void
    {
        $this->disable = $disable;
    }
}
