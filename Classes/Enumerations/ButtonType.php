<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Enumerations;

enum ButtonType: string
{
    case Divider = 'divider';
    case Info = 'info';
    case Link = 'link';
    case Menu = 'menu';
}
