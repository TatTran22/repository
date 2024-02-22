<?php

namespace TatTran\Repository;
class Code
{
    public static function generate($code, $prefix = null, $length = 6, $str = '0')
    {
        $formattedCode = str_pad($code, $length, $str, STR_PAD_LEFT);

        if ($prefix) {
            return $prefix . $formattedCode;
        }

        return $formattedCode;
    }
}
