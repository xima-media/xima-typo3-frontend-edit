<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Utility;

class StringUtility
{
    public static function shortenString(string $string, int $maxLength = 30): string
    {
        if (mb_strlen($string) <= $maxLength) {
            return $string;
        }

        return mb_substr($string, 0, $maxLength) . '…';
    }
}
