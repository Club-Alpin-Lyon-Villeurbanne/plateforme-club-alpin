<?php

use App\Legacy\LegacyContainer;

echo LegacyContainer::get('legacy_twig')->render('right-column.html.twig', [
    'current_commission' => $current_commission ?? null,
]);
