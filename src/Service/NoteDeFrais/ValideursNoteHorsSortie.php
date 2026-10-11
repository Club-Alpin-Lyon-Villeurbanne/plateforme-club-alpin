<?php

namespace App\Service\NoteDeFrais;

use App\Entity\ExpenseReport;
use App\Entity\User;

/**
 * Les personnes qui valident une note hors sortie avant la trésorerie.
 */
class ValideursNoteHorsSortie
{
    /**
     * Une liste vide signifie que la note va directement à la trésorerie.
     *
     * @return list<User>
     */
    public function pour(ExpenseReport $note): array
    {
        return [];
    }
}
