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
    /** @var array<string, mixed> barème des notes hors sortie, lu seulement pour elles */
    private array $horsSortie;

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
        $this->horsSortie = $config['horsSortie'] ?? [];
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
        $distance = $this->nombre($transport['distance'] ?? null);
        $tollFee = $this->nombre($transport['tollFee'] ?? null);
        $fuelExpense = $this->nombre($transport['fuelExpense'] ?? null);
        $passengerCount = $this->nombre($transport['passengerCount'] ?? null);

        $total = match ($transport['type'] ?? null) {
            'PERSONAL_VEHICLE' => ($distance * $this->tauxKilometriqueVoiture) + ($tollFee / $this->divisionPeage),
            'CLUB_MINIBUS' => $passengerCount > 0 ? (($distance * $this->tauxKilometriqueMinibus) + $fuelExpense + $tollFee) / $passengerCount : 0.0,
            'RENTAL_MINIBUS' => $passengerCount > 0 ? ($this->nombre($transport['rentalPrice'] ?? null) + $fuelExpense + $tollFee) / $passengerCount : 0.0,
            'PUBLIC_TRANSPORT' => $this->nombre($transport['ticketPrice'] ?? null),
            // Type inconnu ou non renseigné
            default => 0.0,
        };

        return $this->nombre($total);
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
            $price = $this->nombre($acc['price'] ?? null);
            $res['total'] += $price;
            // Plafond par nuit
            $res['reimbursable'] += min($price, $this->nuiteeMaxRemboursable);
        }

        return array_map($this->nombre(...), $res);
    }

    /**
     * Calcule le total des "autres" dépenses (entièrement remboursables).
     */
    public function calculateOthersTotal(array $others): float
    {
        $sum = 0.0;
        foreach ($others as $other) {
            $sum += $this->nombre($other['price'] ?? null);
        }

        return $this->nombre($sum);
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
        $transportData = \is_array($details['transport'] ?? null) ? $details['transport'] : [];
        $transportTotal = $this->calculateTransportTotal($transportData);

        // Hébergements
        $accommodationsData = \is_array($details['accommodations'] ?? null) ? $details['accommodations'] : [];
        $accTotals = $this->calculateAccommodationTotals($accommodationsData);

        // Autres dépenses
        $othersData = \is_array($details['others'] ?? null) ? $details['others'] : [];
        $othersTotal = $this->calculateOthersTotal($othersData);

        // Somme brute
        $total = $transportTotal + $accTotals['total'] + $othersTotal;
        // Somme remboursable
        $reimbursable = $transportTotal + $accTotals['reimbursable'] + $othersTotal;

        return [
            'total' => $this->nombre($total),
            'reimbursable' => $this->nombre($reimbursable),
        ];
    }

    /**
     * Total déclaré et total remboursable d'une note, quel que soit son type. Ceux d'une note de
     * sortie suivent toujours les taux en vigueur, comme sur la page de la sortie et dans ses mails.
     *
     * @return array{totalDeclare: float, totalRemboursable: float}
     */
    public function montants(ExpenseReport $note): array
    {
        if ($note->getType()->estHorsSortie()) {
            return $this->montantsNoteHorsSortie($note);
        }

        $details = json_decode($note->getDetails() ?? '', true);
        $totaux = $this->calculateTotal(\is_array($details) ? $details : []);

        return ['totalDeclare' => round($totaux['total'], 2), 'totalRemboursable' => round($totaux['reimbursable'], 2)];
    }

    /**
     * Montants d'une note hors sortie : ceux figés à son dernier envoi, sauf quand elle est chez
     * son demandeur (brouillon ou à corriger), où ils suivent ses lignes et le barème en vigueur.
     *
     * @return array{totalDeclare: float, totalRemboursable: float}
     */
    public function montantsNoteHorsSortie(ExpenseReport $note): array
    {
        if ($this->estFigee($note) && null !== $note->getTotalDeclare()) {
            return [
                'totalDeclare' => (float) $note->getTotalDeclare(),
                'totalRemboursable' => (float) $note->getTotalRemboursable(),
            ];
        }

        return $this->calculerNoteHorsSortie($note);
    }

    /**
     * À l'envoi : fige les montants, et le taux kilométrique d'une formation, du barème en vigueur.
     */
    public function figerNoteHorsSortie(ExpenseReport $note): void
    {
        $montants = $this->calculerNoteHorsSortie($note);
        $taux = ExpenseReportTypeEnum::FORMATION === $note->getType() ? $this->tauxKilometriqueEnVigueur() : null;
        $note->figerMontants($montants['totalDeclare'], $montants['totalRemboursable'], $taux);
    }

    /**
     * Total déclaré et total remboursable d'après les lignes et le barème en vigueur : plafonds de
     * chaque nature, sur chaque ligne ou sur le total de la nature dans la note, puis plafond du
     * total de la note.
     *
     * @return array{totalDeclare: float, totalRemboursable: float}
     */
    public function calculerNoteHorsSortie(ExpenseReport $note): array
    {
        $parNature = $this->bareme($note->getType()->value, 'plafonds', 'parNature');
        $plafondDuTotal = $this->bareme($note->getType()->value, 'plafonds', 'total');
        $taux = $this->tauxKilometriqueEnVigueur();
        $total = 0.0;
        $remboursable = 0.0;
        $sommesParNature = [];
        foreach ($note->lignes() as $ligne) {
            $montant = $this->montantDeLaLigne($ligne, $taux);
            $total += $montant;

            $nature = $ligne['nature'] ?? null;
            $plafond = \is_string($nature) ? ($parNature[$nature] ?? null) : null;
            if (null === $plafond) {
                $remboursable += $montant;
            } elseif ('ligne' === $plafond['portee']) {
                $remboursable += min($montant, (float) $plafond['montant']);
            } else {
                $sommesParNature[$nature] = ($sommesParNature[$nature] ?? 0.0) + $montant;
            }
        }
        // Une portée inconnue de la configuration lève une erreur au lieu d'être ignorée.
        foreach ($sommesParNature as $nature => $somme) {
            $remboursable += match ($parNature[$nature]['portee']) {
                'note' => min($somme, (float) $parNature[$nature]['montant']),
            };
        }
        if (null !== $plafondDuTotal) {
            $remboursable = min($remboursable, (float) $plafondDuTotal);
        }

        return ['totalDeclare' => round($this->nombre($total), 2), 'totalRemboursable' => round($this->nombre($remboursable), 2)];
    }

    /**
     * Lignes d'une note hors sortie telles que le détail et les mails les montrent : la description
     * d'un achat ou la nature d'une formation, le calcul de la voiture ou la description d'une
     * dépense « Autre », et le montant retenu. Une note envoyée garde le taux kilométrique de son
     * envoi.
     *
     * @return list<array{id: ?string, nature: ?string, libelle: ?string, detail: ?string, montant: float}>
     */
    public function lignesAffichees(ExpenseReport $note): array
    {
        $natures = $note->getType()->natures();
        $taux = $this->tauxDesLignes($note);

        return array_map(function (array $ligne) use ($natures, $taux): array {
            $nature = \is_string($ligne['nature'] ?? null) ? $ligne['nature'] : null;
            $libelle = null !== $nature ? ($natures[$nature] ?? $nature) : ($ligne['description'] ?? null);
            $distance = $ligne['distance'] ?? null;

            return [
                'id' => \is_string($ligne['id'] ?? null) ? $ligne['id'] : null,
                'nature' => $nature,
                'libelle' => \is_string($libelle) && '' !== $libelle ? $libelle : null,
                'detail' => match (true) {
                    'distance' === ExpenseReportTypeEnum::champDeLaSaisie($ligne) && (\is_int($distance) || \is_float($distance)) => sprintf('%s km × %s', (new \NumberFormatter('fr_FR', \NumberFormatter::DECIMAL))->format($this->nombre($distance)), $this->formatEuros($taux)),
                    ExpenseReportTypeEnum::NATURE_A_PRECISER === $nature => ExpenseReportTypeEnum::descriptionDeLaLigne($ligne),
                    default => null,
                },
                'montant' => $this->montantDeLaLigne($ligne, $taux),
            ];
        }, $note->lignes());
    }

    /**
     * « dont X € remboursables selon les plafonds du club », à lire avec le total quand un plafond
     * s'applique. Un don n'est pas remboursé : « dont X € dans les plafonds du club », sans rien
     * promettre du reçu.
     *
     * @param array{totalDeclare: float, totalRemboursable: float} $montants
     */
    public function partRemboursable(array $montants, bool $don = false): ?string
    {
        if ($montants['totalRemboursable'] >= $montants['totalDeclare']) {
            return null;
        }

        return sprintf($don ? 'dont %s dans les plafonds du club' : 'dont %s remboursables selon les plafonds du club', $this->formatEuros($montants['totalRemboursable']));
    }

    public function tauxKilometriqueEnVigueur(): float
    {
        return (float) $this->bareme('formation', 'tauxKilometrique');
    }

    /**
     * Une valeur du barème hors sortie ; une clé absente ou renommée arrête le calcul au lieu de
     * donner un taux ou un plafond nul. Une valeur peut être nulle (~ : pas de plafond).
     */
    private function bareme(string ...$cles): mixed
    {
        $valeur = $this->horsSortie;
        foreach ($cles as $cle) {
            if (!\is_array($valeur) || !\array_key_exists($cle, $valeur)) {
                throw new \LogicException(sprintf('Paramètre expense_report.horsSortie.%s absent de la configuration.', implode('.', $cles)));
            }
            $valeur = $valeur[$cle];
        }

        return $valeur;
    }

    // Une note qui a quitté son demandeur garde ce qui a été figé à son envoi.
    private function estFigee(ExpenseReport $note): bool
    {
        return !$note->getStatus()?->isEditableByOwner();
    }

    /**
     * Montant retenu pour une ligne : kilomètres × taux pour la voiture, montant saisi sinon ; zéro
     * tant que la ligne n'est pas remplie.
     *
     * @param array<string, mixed> $ligne
     */
    private function montantDeLaLigne(array $ligne, float $taux): float
    {
        $champ = ExpenseReportTypeEnum::champDeLaSaisie($ligne);
        $saisie = $this->nombre($ligne[$champ] ?? null);

        return $this->nombre('distance' === $champ ? round($saisie * $taux, 2) : $saisie);
    }

    /**
     * Taux kilométrique des lignes d'une formation : celui figé à son dernier envoi, sinon celui en
     * vigueur.
     */
    private function tauxDesLignes(ExpenseReport $note): float
    {
        $fige = $this->estFigee($note) ? $note->tauxKilometriqueFige() : null;

        return null !== $fige ? $this->nombre($fige) : $this->tauxKilometriqueEnVigueur();
    }

    /**
     * Toute valeur lue dans `details`, ou calculée à partir d'elle, passe par ici : un nombre fini,
     * sinon 0. Un texte ou un `1e400` (infini) enregistré en base ne doit faire échouer aucune
     * lecture, puisque l'encodage JSON refuse l'infini.
     */
    private function nombre(mixed $valeur): float
    {
        $nombre = is_numeric($valeur) ? (float) $valeur : 0.0;

        return is_finite($nombre) ? $nombre : 0.0;
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
