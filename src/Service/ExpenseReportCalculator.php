<?php

namespace App\Service;

use App\Entity\ExpenseReport;

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
     * Un achat est retenu au réel, sans plafond : le total remboursable vaut le total déclaré.
     *
     * @return array{totalDeclare: float, totalRemboursable: float}
     */
    public function calculerNoteHorsSortie(ExpenseReport $note): array
    {
        $total = 0.0;
        foreach ($note->lignes() as $ligne) {
            $montant = $ligne['montant'] ?? null;
            if (\is_int($montant) || \is_float($montant)) {
                $total += $montant;
            }
        }
        $total = round($total, 2);

        return ['totalDeclare' => $total, 'totalRemboursable' => $total];
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
