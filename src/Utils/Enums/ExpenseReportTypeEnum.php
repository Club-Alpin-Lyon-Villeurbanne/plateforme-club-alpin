<?php

namespace App\Utils\Enums;

/**
 * Type d'une note de frais. Ne porte que la structure ; les règles de la politique de
 * remboursement (délai, seuils, justificatifs) vivent dans le module de contrôles, le barème
 * dans la configuration.
 */
enum ExpenseReportTypeEnum: string
{
    case SORTIE = 'sortie';
    case FORMATION = 'formation';
    // « Achat ou autre dépense » à l'écran.
    case AUTRE = 'autre';

    // Une ligne de voiture se saisit en kilomètres (`distance`), toutes les autres en euros (`montant`).
    public const string NATURE_EN_KILOMETRES = 'voiture';

    public function estHorsSortie(): bool
    {
        return self::SORTIE !== $this;
    }

    /**
     * « formation pour Escalade », « Type : achat ».
     */
    public function libelle(): string
    {
        return match ($this) {
            self::SORTIE => 'sortie',
            self::FORMATION => 'formation',
            self::AUTRE => 'achat',
        };
    }

    public function libelleDeLaDate(): string
    {
        return match ($this) {
            self::SORTIE => 'Date de la sortie',
            self::FORMATION => 'Date de la formation',
            self::AUTRE => 'Date de l\'achat',
        };
    }

    /**
     * Natures qu'une ligne de la note peut porter, avec leur libellé, dans l'ordre de la saisie.
     *
     * @return array<string, string>
     */
    public function natures(): array
    {
        return match ($this) {
            self::FORMATION => [
                'stage' => 'Stage',
                self::NATURE_EN_KILOMETRES => 'Voiture',
                'peage' => 'Péage',
                'train' => 'Train ou bus',
                // Une ligne par nuit : le plafond de l'hébergement vaut par nuit.
                'hebergement' => 'Nuit d\'hébergement',
                'autre' => 'Autre',
            ],
            self::SORTIE, self::AUTRE => [],
        };
    }

    /**
     * @return list<string>
     */
    public function naturesPermises(): array
    {
        return array_keys($this->natures());
    }

    /**
     * Une note de sortie va directement à la trésorerie ; une note hors sortie passe d'abord
     * par un responsable de sa commission.
     */
    public function passeParUnResponsable(): bool
    {
        return $this->estHorsSortie();
    }

    /**
     * Champ d'une ligne qui porte sa saisie : `distance` pour la voiture, `montant` sinon.
     *
     * @param array<string, mixed> $ligne
     */
    public static function champDeLaSaisie(array $ligne): string
    {
        return self::NATURE_EN_KILOMETRES === ($ligne['nature'] ?? null) ? 'distance' : 'montant';
    }
}
