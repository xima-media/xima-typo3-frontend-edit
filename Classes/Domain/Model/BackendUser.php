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

namespace Xima\XimaTypo3FrontendEdit\Domain\Model;

use TYPO3\CMS\Beuser\Domain\Model\BackendUser as User;

/**
 * BackendUser.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-2.0
 */
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
