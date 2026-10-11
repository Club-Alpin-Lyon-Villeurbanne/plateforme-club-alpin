<?php

namespace App\Utils\Enums;

/**
 * Type d'une note de frais. Ne porte que la structure ; les règles de la politique de
 * remboursement (délai, seuils, justificatifs) vivent dans le module de contrôles.
 */
enum ExpenseReportTypeEnum: string
{
    case SORTIE = 'sortie';
    case FORMATION = 'formation';
    // « Achat ou autre dépense » à l'écran.
    case AUTRE = 'autre';

    public function estHorsSortie(): bool
    {
        return self::SORTIE !== $this;
    }

    /**
     * Natures qu'une ligne de la note peut porter.
     *
     * @return list<string>
     */
    public function naturesPermises(): array
    {
        return match ($this) {
            self::FORMATION => ['stage', 'voiture', 'peage', 'train', 'hebergement', 'autre'],
            self::SORTIE, self::AUTRE => [],
        };
    }

    /**
     * Une note de sortie va directement à la trésorerie ; une note hors sortie passe d'abord
     * par un responsable de sa commission.
     */
    public function passeParUnResponsable(): bool
    {
        return $this->estHorsSortie();
    }
}
