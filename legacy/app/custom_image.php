<?php

    // Images customisées pour chaque client

    // Appelé depuis .htaccess */
    // RewriteRule ^img/(adresse-website\.png|logo\.png)$  /index.php?cstImg=$1 [QSA,L]

    $mimes = [
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'bpm' => 'image/bpm',
    ];

    $image = __DIR__.'/../config/'.$_SERVER['HTTP_HOST'].'/img/'.$_GET['cstImg'];
    $exp = explode('.', $_GET['cstImg']);
    $ext = strtolower(end($exp));

    if (file_exists($image)) {
        header('content-type: '.$mimes[$ext]);
        header('content-disposition: inline; filename="'.$_GET['cstImg'].'";');
        readfile($image);
    } else {
        $imageGenerique = __DIR__.'/../../public/img/'.$_GET['cstImg'];
        $exp = explode('.', $_GET['cstImg']);
        $ext = strtolower(end($exp));
        if (file_exists($imageGenerique)) {
            header('content-type: '.$mimes[$ext]);
            header('content-disposition: inline; filename="'.$_GET['cstImg'].'";');
            readfile($imageGenerique);
        }
    }
