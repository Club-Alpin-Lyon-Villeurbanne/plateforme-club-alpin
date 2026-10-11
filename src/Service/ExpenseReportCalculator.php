<?php

namespace App\Service;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportTypeEnum;

/**
 * Service pour calculer et formater une note de frais
 * à partir d'un tableau associatif (décodé depuis le JSON).
 */
class ExpenseReportCalculator
{
    private float $nuiteeMaxRemboursable;
    private float $tauxKilometriqueVoiture;
    private float $tauxKilometriqueMinibus;
    private float $divisionPeage;
    private float $tauxKilometriqueFormation;
    /** @var array<string, array{montant: int|float, portee: string}> */
    private array $plafondsFormation;

    /**
     * @param array $config Exemple :
     *                      [
     *                      'nuiteeMaxRemboursable'   => 60,
     *                      'tauxKilometriqueVoiture' => 0.2,
     *                      'tauxKilometriqueMinibus' => 0.3,
     *                      'divisionPeage'           => 3,
     *                      ]
     */
    public function __construct(array $config)
    {
        $this->nuiteeMaxRemboursable = $config['nuiteeMaxRemboursable'] ?? 0.0;
        $this->tauxKilometriqueVoiture = $config['tauxKilometriqueVoiture'] ?? 0.0;
        $this->tauxKilometriqueMinibus = $config['tauxKilometriqueMinibus'] ?? 0.0;
        $this->divisionPeage = $config['divisionPeage'] ?? 1.0;
        $this->tauxKilometriqueFormation = (float) ($config['horsSortie']['formation']['tauxKilometrique'] ?? 0.0);
        $this->plafondsFormation = $config['horsSortie']['formation']['plafonds'] ?? [];
    }

    /**
     * Calcule le total transport selon le type :
     * - PERSONAL_VEHICLE : distance * taux + (tollFee / divisionPeage)
     * - CLUB_MINIBUS     : (distance * tauxMinibus + fuel + tollFee) / nbPassagers
     * - RENTAL_MINIBUS   : (rentalPrice + fuel + tollFee) / nbPassagers
     * - PUBLIC_TRANSPORT : ticketPrice.
     *
     * @param array $transport Par ex. :
     *                         [
     *                         'type'          => 'PERSONAL_VEHICLE',
     *                         'distance'      => 120,
     *                         'tollFee'       => 10,
     *                         ...
     *                         ]
     */
    public function calculateTransportTotal(array $transport): float
    {
        $type = $transport['type'] ?? null;

        switch ($type) {
            case 'PERSONAL_VEHICLE':
                $distance = $transport['distance'] ?? 0.0;
                $tollFee = $transport['tollFee'] ?? 0.0;

                return ($distance * $this->tauxKilometriqueVoiture)
                     + ($tollFee / $this->divisionPeage);

            case 'CLUB_MINIBUS':
                $distance = $transport['distance'] ?? 0.0;
                $fuelExpense = $transport['fuelExpense'] ?? 0.0;
                $tollFee = $transport['tollFee'] ?? 0.0;
                $passengerCount = $transport['passengerCount'] ?? 0;
                if ($passengerCount <= 0) {
                    return 0.0;
                }
                $total = ($distance * $this->tauxKilometriqueMinibus)
                       + $fuelExpense
                       + $tollFee;

                return $total / $passengerCount;

            case 'RENTAL_MINIBUS':
                $rentalPrice = $transport['rentalPrice'] ?? 0.0;
                $fuelExpense = $transport['fuelExpense'] ?? 0.0;
                $tollFee = $transport['tollFee'] ?? 0.0;
                $passengerCount = $transport['passengerCount'] ?? 0;
                if ($passengerCount <= 0) {
                    return 0.0;
                }

                return ($rentalPrice + $fuelExpense + $tollFee) / $passengerCount;

            case 'PUBLIC_TRANSPORT':
                return $transport['ticketPrice'] ?? 0.0;

            default:
                // Type inconnu ou non renseigné
                return 0.0;
        }
    }

    /**
     * Calcule total & total remboursable pour les hébergements.
     */
    public function calculateAccommodationTotals(array $accommodations): array
    {
        $res = [
            'total' => 0.0,
            'reimbursable' => 0.0,
        ];

        foreach ($accommodations as $acc) {
            $price = (float) ($acc['price'] ?? 0);
            $res['total'] += $price;
            // Plafond par nuit
            $res['reimbursable'] += min($price, $this->nuiteeMaxRemboursable);
        }

        return $res;
    }

    /**
     * Calcule le total des "autres" dépenses (entièrement remboursables).
     */
    public function calculateOthersTotal(array $others): float
    {
        $sum = 0.0;
        foreach ($others as $other) {
            $sum += (float) ($other['price'] ?? 0);
        }

        return $sum;
    }

    /**
     * Calcule le total brut et le total remboursable de la note de frais.
     *
     * $details doit ressembler à :
     * [
     *   'transport' => [...],
     *   'accommodations' => [...],
     *   'others' => [...]
     * ]
     */
    public function calculateTotal(array $details): array
    {
        // Transport
        $transportData = $details['transport'] ?? [];
        $transportTotal = $this->calculateTransportTotal($transportData);

        // Hébergements
        $accommodationsData = $details['accommodations'] ?? [];
        $accTotals = $this->calculateAccommodationTotals($accommodationsData);

        // Autres dépenses
        $othersData = $details['others'] ?? [];
        $othersTotal = $this->calculateOthersTotal($othersData);

        // Somme brute
        $total = $transportTotal + $accTotals['total'] + $othersTotal;
        // Somme remboursable
        $reimbursable = $transportTotal + $accTotals['reimbursable'] + $othersTotal;

        return [
            'total' => $total,
            'reimbursable' => $reimbursable,
        ];
    }

    /**
     * Montants d'une note hors sortie : ceux figés à son dernier envoi, sauf quand elle est chez
     * son demandeur (brouillon ou à corriger), où ils suivent ses lignes et le barème en vigueur.
     *
     * @return array{totalDeclare: float, totalRemboursable: float}
     */
    public function montantsNoteHorsSortie(ExpenseReport $note): array
    {
        if (!$note->getStatus()?->isEditableByOwner() && null !== $note->getTotalDeclare()) {
            return [
                'totalDeclare' => (float) $note->getTotalDeclare(),
                'totalRemboursable' => (float) $note->getTotalRemboursable(),
            ];
        }

        return $this->calculerNoteHorsSortie($note);
    }

    /**
     * Total déclaré et total remboursable d'après les lignes et le barème en vigueur. Un achat est
     * retenu au réel, sans plafond. Une formation applique les plafonds de chaque nature, sur
     * chaque ligne ou sur le total de la nature dans la note.
     *
     * @return array{totalDeclare: float, totalRemboursable: float}
     */
    public function calculerNoteHorsSortie(ExpenseReport $note): array
    {
        $plafonds = ExpenseReportTypeEnum::FORMATION === $note->getType() ? $this->plafondsFormation : [];
        $total = 0.0;
        $remboursable = 0.0;
        $parNature = [];
        foreach ($note->lignes() as $ligne) {
            $montant = $this->montantDeLaLigne($ligne);
            $total += $montant;

            $nature = $ligne['nature'] ?? null;
            $plafond = \is_string($nature) ? ($plafonds[$nature] ?? null) : null;
            if (null === $plafond) {
                $remboursable += $montant;
            } elseif ('ligne' === $plafond['portee']) {
                $remboursable += min($montant, (float) $plafond['montant']);
            } else {
                $parNature[$nature] = ($parNature[$nature] ?? 0.0) + $montant;
            }
        }
        // Une portée inconnue de la configuration lève une erreur au lieu d'être ignorée.
        foreach ($parNature as $nature => $somme) {
            $remboursable += match ($plafonds[$nature]['portee']) {
                'note' => min($somme, (float) $plafonds[$nature]['montant']),
            };
        }

        return ['totalDeclare' => round($total, 2), 'totalRemboursable' => round($remboursable, 2)];
    }

    /**
     * Montant retenu pour une ligne : kilomètres × taux pour la voiture, montant saisi sinon ; zéro
     * tant que la ligne n'est pas remplie.
     *
     * @param array<string, mixed> $ligne
     */
    public function montantDeLaLigne(array $ligne): float
    {
        $champ = ExpenseReportTypeEnum::champDeLaSaisie($ligne);
        $saisie = $ligne[$champ] ?? null;
        if (!\is_int($saisie) && !\is_float($saisie)) {
            return 0.0;
        }

        return 'distance' === $champ
            ? round($saisie * $this->tauxKilometriqueFormation, 2)
            : (float) $saisie;
    }

    /**
     * Lignes d'une note hors sortie telles que les mails les montrent : la description d'un achat
     * ou la nature d'une formation, le détail de son calcul, et le montant retenu.
     *
     * @return list<array{id: ?string, libelle: ?string, detail: ?string, montant: string}>
     */
    public function lignesAffichees(ExpenseReport $note): array
    {
        $natures = $note->getType()->natures();

        return array_map(function (array $ligne) use ($natures): array {
            $nature = $ligne['nature'] ?? null;
            $libelle = \is_string($nature) ? ($natures[$nature] ?? $nature) : ($ligne['description'] ?? null);

            return [
                'id' => \is_string($ligne['id'] ?? null) ? $ligne['id'] : null,
                'libelle' => \is_string($libelle) && '' !== $libelle ? $libelle : null,
                'detail' => $this->detailDeLaLigne($ligne),
                'montant' => $this->formatEuros($this->montantDeLaLigne($ligne)),
            ];
        }, $note->lignes());
    }

    /**
     * « 220 km × 0,40 € » pour la voiture, « 1 nuit » pour l'hébergement, qui se déclare nuit par nuit.
     *
     * @param array<string, mixed> $ligne
     */
    private function detailDeLaLigne(array $ligne): ?string
    {
        $distance = $ligne['distance'] ?? null;

        return match ($ligne['nature'] ?? null) {
            ExpenseReportTypeEnum::NATURE_EN_KILOMETRES => \is_int($distance) || \is_float($distance)
                ? sprintf('%s km × %s', (new \NumberFormatter('fr_FR', \NumberFormatter::DECIMAL))->format($distance), $this->formatEuros($this->tauxKilometriqueFormation))
                : null,
            'hebergement' => '1 nuit',
            default => null,
        };
    }

    /**
     * Formatte un montant en euros (ex: "123,45 €").
     */
    public function formatEuros(float $amount): string
    {
        $formatter = new \NumberFormatter('fr_FR', \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, 'EUR');
    }

    /**
     * [Optionnel] Si tu veux juste avoir accès aux taux.
     */
    public function getTauxKilometriqueVoiture(): float
    {
        return $this->tauxKilometriqueVoiture;
    }

    public function getTauxKilometriqueMinibus(): float
    {
        return $this->tauxKilometriqueMinibus;
    }
}
