<?php

// redirection vers la nouvelle (si des gens l'ont en favoris ou autres)
use App\Legacy\LegacyContainer;

header('Location: ' . LegacyContainer::get('router')->generate('creer_sortie'));
exit;
