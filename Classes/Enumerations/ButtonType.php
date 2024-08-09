<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Enumerations;

enum ButtonType: string
{
    case Divider = 'divider';
    case Header = 'header';
    case Link = 'link';
    case Menu = 'menu';
}
