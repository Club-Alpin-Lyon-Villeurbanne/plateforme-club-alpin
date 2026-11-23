<?php

namespace App\Utils;

use Symfony\Component\String\UnicodeString;

class StringUtils
{
    public static function removeDiacritics(string $string)
    {
        return (string) (new UnicodeString($string))->ascii();
    }

    /**
     * Forme comparable d'un nom ou d'un prénom : sans accents, espaces, tirets,
     * apostrophes ni majuscules (« Jean-Pierre » → « jeanpierre », « DU PONT » → « dupont »).
     */
    public static function normalizeName(?string $name): string
    {
        return preg_replace('/[^a-z]/', '', strtolower(self::removeDiacritics($name ?? '')));
    }
}
