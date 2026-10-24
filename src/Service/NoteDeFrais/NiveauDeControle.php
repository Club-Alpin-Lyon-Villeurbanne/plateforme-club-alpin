<?php

namespace App\Service\NoteDeFrais;

enum NiveauDeControle
{
    // L'API refuse la requête.
    case BLOQUANT;
    // L'API expose le message, rien n'est refusé.
    case AVERTISSEMENT;
}
