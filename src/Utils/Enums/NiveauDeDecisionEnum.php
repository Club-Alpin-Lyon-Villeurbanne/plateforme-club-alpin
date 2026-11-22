<?php

namespace App\Utils\Enums;

/**
 * Qui a décidé d'une note hors sortie : un responsable de sa commission, ou la trésorerie.
 */
enum NiveauDeDecisionEnum: string
{
    case RESPONSABLE = 'responsable';
    case TRESORERIE = 'tresorerie';
}
