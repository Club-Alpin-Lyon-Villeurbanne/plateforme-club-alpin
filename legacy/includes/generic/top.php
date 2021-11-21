<?php

include __DIR__.'/../../includes/browser-alert.php';

echo twigRender('header.html.twig', [
    'current_commission' => $current_commission,
    'p1' => $p1,
]);
