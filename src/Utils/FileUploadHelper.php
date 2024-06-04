<?php

namespace App\Utils;

use App\Entity\User;

class FileUploadHelper
{
    public static function getUserUploadPath(User $user, $subdir): string
    {
        $subdir = trim($subdir, '/');

        return __DIR__ . '/../../public/ftp/user/' . $user->getId() . '/' . $subdir;
    }

    public static function getUserUploadUrl(User $user, $subdir): string
    {
        $subdir = trim($subdir, '/');

        return '/ftp/user/' . $user->getId() . '/' . $subdir;
    }
}
