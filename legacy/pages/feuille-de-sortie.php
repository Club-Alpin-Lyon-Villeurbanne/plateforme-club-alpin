<?php

if (!$evt && !$destination) {
    include __DIR__.'/404.php';
    exit;
}

if ($evt) {
    include __DIR__.'/../includes/evt/feuille_de_sortie.php';
} elseif ($destination) {
    include __DIR__.'/../includes/dest/feuille_de_sortie.php';
}
