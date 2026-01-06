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
}
