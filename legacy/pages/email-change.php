<?php

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

global $kernel;

echo '<div class="contenutype1" style="position:relative;z-index:5;margin:30px auto;"><h2 style="color:gray">Reinitialisation de l\'email...</h2>';
if (isset($errTab) && count($errTab) > 0) {
    echo '<div class="erreur"><b>ERREURS : </b>'.implode(', ', $errTab).'</div>';
} else {
    echo '<h1>Succès</h1><p>Vous pouvez vous connecter avec votre nouvelle adresse e-mail.</p>';
}
echo '<a class="nice2 green" href="'.$kernel->getContainer()->get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'profil.html" title="">Continuer</a>';
echo '</div>';
