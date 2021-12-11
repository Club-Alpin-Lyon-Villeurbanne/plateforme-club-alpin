<?php

namespace App\Ftp;

class FtpFile
{
    public static function shouldHide(string $file)
    {
        return \in_array($file, ['index.php', '.', '..', '.htaccess', 'Thumbs.db', 'transit', 'article', 'articles', 'commission', 'user', 'sorties', 'galeries', 'partenaires'], true);
    }

    public static function isProtected(string $file)
    {
        return \in_array($file, ['images', 'telechargements', 'transit', 'fichiers-proteges'], true);
    }

    public static function getAllowedExtensions(): array
    {
        return ['gpx', 'kml', 'kmz', 'jpg', 'gif', 'jpeg', 'png', 'doc', 'docx', 'odt', 'pdf', 'avi', 'mov', 'mp3', 'rar', 'zip', 'txt', 'xls', 'csv', 'ppt', 'pptx', 'ai', 'psd', 'fla', 'swf', 'eps'];
    }
}
