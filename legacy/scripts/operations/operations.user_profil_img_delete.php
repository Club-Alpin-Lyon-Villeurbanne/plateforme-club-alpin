<?php

use App\Legacy\LegacyContainer;

$id_user = getUser()->getId();

if (!isset($errTab) || 0 === count($errTab)) {
    LegacyContainer::get('legacy_fs')->remove([
        __DIR__.'/../../../public/ftp/user/'.$id_user.'/min-profil.jpg',
        __DIR__.'/../../../public/ftp/user/'.$id_user.'/min-profil.png',
        __DIR__.'/../../../public/ftp/user/'.$id_user.'/profil.jpg',
        __DIR__.'/../../../public/ftp/user/'.$id_user.'/profil.png',
    ]);
}
