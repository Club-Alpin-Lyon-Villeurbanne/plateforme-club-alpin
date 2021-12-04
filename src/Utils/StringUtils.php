<?php

namespace App\Utils;

use Symfony\Component\String\UnicodeString;

class StringUtils
{
    public static function removeDiacritics(string $string)
    {
        return (string) (new UnicodeString($string))->ascii();
    }
}
