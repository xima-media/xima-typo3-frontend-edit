<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Domain\Model;

use TYPO3\CMS\Beuser\Domain\Model\BackendUser as User;

class BackendUser extends User
{
    protected bool $hide = false;

    public function isHide(): bool
    {
        return $this->hide;
    }

    public function setHide(bool $hide): void
    {
        $this->hide = $hide;
    }
}
