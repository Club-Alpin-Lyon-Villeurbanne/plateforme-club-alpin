<?php

namespace App\Utils;

class NicknameGenerator
{
    public static function generateNickname($firstName, $lastName)
    {
        $nickname = str_replace([' ', '_', '-', '\''], '', ucfirst(strtolower(StringUtils::removeDiacritics($firstName))) . strtoupper(StringUtils::removeDiacritics($lastName[0] ?? '')));

        $md5 = md5($firstName . $lastName);
        $uniq = substr($md5, 1, 2) . substr($md5, 14, 1);
        $nickname .= '-' . $uniq;

        return $nickname;
    }
}
