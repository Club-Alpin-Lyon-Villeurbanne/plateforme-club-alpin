<?php

namespace App\Helper;

class HtmlHelper
{
    /**
     * Escape a string for safe HTML output.
     *
     * Converts special characters to HTML entities using UTF-8 encoding.
     * Handles null values gracefully by returning an empty string.
     */
    public static function escape(?string $str): string
    {
        return htmlentities($str ?? '', \ENT_QUOTES, 'UTF-8');
    }

    /**
     * Description du journal d'administration prête à afficher.
     *
     * Les anciennes lignes encadraient le code du bloc de balises <i> : seul ce format est nettoyé,
     * pour ne pas altérer un code qui contiendrait lui-même ces caractères.
     */
    public static function escapeAdminLogDescription(string $description): string
    {
        return self::escape(preg_replace('#^(Modif élément : )<i>(.*)</i>$#s', '$1$2', $description));
    }
}
