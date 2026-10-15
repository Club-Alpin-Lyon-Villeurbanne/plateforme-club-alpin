<?php

namespace App\Service\NoteDeFrais;

enum MomentDeControle
{
    case CREATION;
    case CHAQUE_SOUMISSION;
    // Passage de brouillon à envoyée : une note renvoyée puis corrigée n'y est plus soumise.
    case PREMIERE_SOUMISSION;
    case LECTURE;
}
