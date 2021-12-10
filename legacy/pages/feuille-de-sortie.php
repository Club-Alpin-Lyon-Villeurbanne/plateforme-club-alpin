<?php

if (!$evt && !$destination) {
    require __DIR__.'/../pages/404.php';
    exit;
}

if ($evt) {
    require __DIR__.'/../includes/evt/feuille_de_sortie.php';
} elseif ($destination) {
    require __DIR__.'/../includes/dest/feuille_de_sortie.php';
}
