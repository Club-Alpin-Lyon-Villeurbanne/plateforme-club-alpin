<?php

$id_user = getUser()->getIdUser();

if (!isset($errTab) || 0 === count($errTab)) {
    if (is_file(__DIR__.'/../../../public/ftp/user/'.$id_user.'/min-profil.jpg')) {
        unlink(__DIR__.'/../../../public/ftp/user/'.$id_user.'/min-profil.jpg');
    }
    if (is_file(__DIR__.'/../../../public/ftp/user/'.$id_user.'/min-profil.png')) {
        unlink(__DIR__.'/../../../public/ftp/user/'.$id_user.'/min-profil.png');
    }
    if (is_file(__DIR__.'/../../../public/ftp/user/'.$id_user.'/profil.jpg')) {
        unlink(__DIR__.'/../../../public/ftp/user/'.$id_user.'/profil.jpg');
    }
    if (is_file(__DIR__.'/../../../public/ftp/user/'.$id_user.'/profil.png')) {
        unlink(__DIR__.'/../../../public/ftp/user/'.$id_user.'/profil.png');
    }
}
