<?php

namespace App\Utils;

class NicknameGenerator
{
    public static function generateNickname(string $firstName, string $lastName, string $cafnum): string
    {
        $nickname = str_replace([' ', '_', '-', '\''], '', ucfirst(strtolower(StringUtils::removeDiacritics($firstName))) . strtoupper(StringUtils::removeDiacritics($lastName[0] ?? '')));

        $md5 = md5($firstName . $lastName . $cafnum);
        $uniq = substr($md5, 1, 2) . substr($md5, 14, 1);
        $nickname .= '-' . $uniq;

        return $nickname;
    }
}
