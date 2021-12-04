<?php

namespace App\Utils;

class NicknameGenerator
{
    public static function generateNickname($firstName, $lastName)
    {
        $nickname = str_replace([' ', '-', '\''], '', ucfirst(strtolower(normalizeChars($firstName))).substr(strtoupper($lastName), 0, 1));

        $md5 = md5($firstName.$lastName);
        $uniq = substr($md5, 1, 2).substr($md5, 14, 1);
        $nickname .= '-'.$uniq;

        return $nickname;
    }
}
