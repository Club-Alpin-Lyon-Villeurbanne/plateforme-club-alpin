<?php

namespace App\Service\NoteDeFrais;

enum MomentDeControle
{
    case CREATION;
    // Le demandeur change la commission d'une note existante.
    case CHANGEMENT_DE_COMMISSION;
    case CHAQUE_SOUMISSION;
    // Passage de brouillon à envoyée : une note renvoyée puis corrigée n'y est plus soumise.
    case PREMIERE_SOUMISSION;
    case LECTURE;
}
