<?php

namespace App\Utils;

use App\Entity\User;

class FileUploadHelper {

    public static function getUserUploadPath(User $user): string
    {
        return __DIR__ . '/../../public/ftp/' . $user->getId();
    }

    public static function getUserUploadUrl(User $user): string
    {
        return '/ftp/' . $user->getId();
    }
}