<?php

namespace App\Service\NoteDeFrais;

use App\Entity\Commission;
use App\Entity\ExpenseAttachment;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Repository\ExpenseReportStatusHistoryRepository;
use App\Service\ExpenseReportCalculator;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Contrôles de la politique de remboursement sur les notes hors sortie.
 *
 * Ajouter un contrôle : écrire une fonction qui renvoie un message lisible par le demandeur et
 * le valideur (null si rien à signaler), puis l'inscrire dans controles() avec ses moments et
 * son niveau. Les seuils se lisent dans la configuration `expense_report`.
 */
final class ControlesNoteHorsSortie
{
    // Rôles qui permettent de demander le remboursement d'une formation sur leur commission.
    private const array ROLES_D_ENCADREMENT = [
        UserAttr::RESPONSABLE_COMMISSION,
        UserAttr::ENCADRANT,
        UserAttr::COENCADRANT,
        UserAttr::STAGIAIRE,
        UserAttr::BENEVOLE,
    ];

    public function __construct(
        #[Autowire(param: 'expense_report')]
        private readonly array $config,
        private readonly ExpenseReportCalculator $calculator,
        private readonly ExpenseReportStatusHistoryRepository $historique,
    ) {
    }

    /**
     * @return list<array{champ: string, message: string}>
     */
    public function bloquants(ExpenseReport $note, MomentDeControle ...$moments): array
    {
        return $this->constats($note, $moments, NiveauDeControle::BLOQUANT);
    }

    /**
     * @return list<string>
     */
    public function avertissements(ExpenseReport $note): array
    {
        return array_column($this->constats($note, [MomentDeControle::LECTURE], NiveauDeControle::AVERTISSEMENT), 'message');
    }

    /**
     * @return list<array{champ: string, moments: list<MomentDeControle>, niveau: NiveauDeControle, controle: callable(ExpenseReport): ?string}>
     */
    private function controles(): array
    {
        return [
            [
                'champ' => 'dateDepense',
                'moments' => [MomentDeControle::CREATION, MomentDeControle::CHAQUE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->dateDansLeFutur(...),
            ],
            [
                'champ' => 'dateDepense',
                'moments' => [MomentDeControle::CREATION, MomentDeControle::PREMIERE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->delaiDEnvoiDepasse(...),
            ],
            [
                'champ' => 'commission',
                'moments' => [MomentDeControle::CREATION, MomentDeControle::CHANGEMENT_DE_COMMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->formationSansRoleDEncadrement(...),
            ],
            [
                'champ' => 'details',
                'moments' => [MomentDeControle::CHAQUE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->sansDepense(...),
            ],
            [
                'champ' => 'details',
                'moments' => [MomentDeControle::CHAQUE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->naturesManquantes(...),
            ],
            [
                'champ' => 'details',
                'moments' => [MomentDeControle::CHAQUE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->depensesAPreciser(...),
            ],
            [
                'champ' => 'details',
                'moments' => [MomentDeControle::CHAQUE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->montantsNonPositifs(...),
            ],
            [
                'champ' => 'details',
                'moments' => [MomentDeControle::CHAQUE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->justificatifsManquants(...),
            ],
            [
                'champ' => 'refundRequired',
                'moments' => [MomentDeControle::CHAQUE_SOUMISSION],
                'niveau' => NiveauDeControle::BLOQUANT,
                'controle' => $this->choixDonOuRemboursementManquant(...),
            ],
            [
                'champ' => 'totalDeclare',
                'moments' => [MomentDeControle::LECTURE],
                'niveau' => NiveauDeControle::AVERTISSEMENT,
                'controle' => $this->auDelaDuSeuil(...),
            ],
            [
                'champ' => 'dateDepense',
                'moments' => [MomentDeControle::LECTURE],
                'niveau' => NiveauDeControle::AVERTISSEMENT,
                'controle' => $this->envoyeeApresLeDelai(...),
            ],
        ];
    }

    private function dateDansLeFutur(ExpenseReport $note): ?string
    {
        $date = $note->getDateDepense();

        return null !== $date && $date > new \DateTimeImmutable('today')
            ? 'La date ne peut pas être dans le futur.'
            : null;
    }

    private function delaiDEnvoiDepasse(ExpenseReport $note): ?string
    {
        $date = $note->getDateDepense();
        $delai = $this->config['horsSortie']['delaiEnvoiJours'][$note->getType()->value] ?? null;

        if (null === $date || null === $delai) {
            return null;
        }

        return $date->modify(sprintf('+%d days', $delai)) < new \DateTimeImmutable('today')
            ? sprintf('Le délai de %d jours après la dépense est dépassé.', $delai)
            : null;
    }

    /**
     * Une note renvoyée repart sans contrôle du délai, et sa date peut changer pendant la
     * correction : le valideur doit le voir. Rien tant que la note est chez son demandeur.
     */
    private function envoyeeApresLeDelai(ExpenseReport $note): ?string
    {
        $date = $note->getDateDepense();
        $delai = $this->config['horsSortie']['delaiEnvoiJours'][$note->getType()->value] ?? null;
        $statut = $note->getStatus();
        if (null === $date || null === $delai || null === $statut || $statut->isEditableByOwner()) {
            return null;
        }

        $envoi = $this->historique->dateDernierEnvoi($note);

        return null !== $envoi && $date->modify(sprintf('+%d days', $delai)) < $envoi->setTime(0, 0)
            ? sprintf('%s daté de plus de %d jours avant son envoi.', ucfirst($note->getType()->libelle()), $delai)
            : null;
    }

    /**
     * Règle du contrôle de création d'une formation, partagée avec la page de demande qui ne
     * propose la formation que sur ces commissions.
     */
    public function peutDemanderUneFormation(User $demandeur, Commission $commission): bool
    {
        return $demandeur->hasAttribute(self::ROLES_D_ENCADREMENT, $commission->getCode());
    }

    private function formationSansRoleDEncadrement(ExpenseReport $note): ?string
    {
        $commission = $note->getCommission();
        $demandeur = $note->getUser();
        if (ExpenseReportTypeEnum::FORMATION !== $note->getType() || null === $commission || null === $demandeur) {
            return null;
        }

        return $this->peutDemanderUneFormation($demandeur, $commission)
            ? null
            : 'Seuls les encadrants de la commission peuvent demander le remboursement d\'une formation.';
    }

    private function sansDepense(ExpenseReport $note): ?string
    {
        return [] === $note->lignes() ? 'Il manque au moins une dépense.' : null;
    }

    // La nature décide du plafond : un brouillon peut l'attendre, pas une note envoyée.
    private function naturesManquantes(ExpenseReport $note): ?string
    {
        if ([] === $note->getType()->naturesPermises()) {
            return null;
        }

        $numeros = $this->numerosDesLignes($note, static fn (array $ligne): bool => !isset($ligne['nature']));

        return match (\count($numeros)) {
            0 => null,
            1 => sprintf('Il manque la nature de la dépense %s.', $numeros[0]),
            default => sprintf('Il manque la nature des dépenses %s.', $this->enumerer($numeros)),
        };
    }

    // Sans description, le valideur ne lirait que « Autre » et le montant.
    private function depensesAPreciser(ExpenseReport $note): ?string
    {
        $libelle = $note->getType()->natures()[ExpenseReportTypeEnum::NATURE_A_PRECISER] ?? null;
        if (null === $libelle) {
            return null;
        }

        $numeros = $this->numerosDesLignes($note, static fn (array $ligne): bool => ExpenseReportTypeEnum::NATURE_A_PRECISER === ($ligne['nature'] ?? null)
            && null === ExpenseReportTypeEnum::descriptionDeLaLigne($ligne));

        return match (\count($numeros)) {
            0 => null,
            1 => sprintf('Précisez la dépense %s : la nature « %s » demande une description.', $numeros[0], $libelle),
            default => sprintf('Précisez les dépenses %s : la nature « %s » demande une description.', $this->enumerer($numeros), $libelle),
        };
    }

    private function montantsNonPositifs(ExpenseReport $note): ?string
    {
        // La distance d'une voiture compte comme son montant.
        $numeros = $this->numerosDesLignes($note, static function (array $ligne): bool {
            $saisie = $ligne[ExpenseReportTypeEnum::champDeLaSaisie($ligne)] ?? null;

            return !(\is_int($saisie) || \is_float($saisie)) || $saisie <= 0;
        });

        return match (\count($numeros)) {
            0 => null,
            1 => sprintf('Le montant de la dépense %s doit être supérieur à zéro.', $numeros[0]),
            default => sprintf('Les montants des dépenses %s doivent être supérieurs à zéro.', $this->enumerer($numeros)),
        };
    }

    private function justificatifsManquants(ExpenseReport $note): ?string
    {
        $lignesJustifiees = $note->getAttachments()->map(static fn (ExpenseAttachment $justificatif) => $justificatif->getExpenseId())->toArray();
        $numeros = $this->numerosDesLignes($note, static fn (array $ligne): bool => ExpenseReportTypeEnum::NATURE_EN_KILOMETRES !== ($ligne['nature'] ?? null)
            && !\in_array($ligne['id'] ?? null, $lignesJustifiees, true));

        return match (\count($numeros)) {
            0 => null,
            1 => sprintf('Il manque le justificatif de la dépense %s.', $numeros[0]),
            default => sprintf('Il manque les justificatifs des dépenses %s.', $this->enumerer($numeros)),
        };
    }

    private function choixDonOuRemboursementManquant(ExpenseReport $note): ?string
    {
        return null === $note->isRefundRequired() ? 'Il manque le choix entre remboursement et don.' : null;
    }

    private function auDelaDuSeuil(ExpenseReport $note): ?string
    {
        $seuil = $this->config['horsSortie']['seuilAvertissement'] ?? null;
        if (null === $seuil || $this->calculator->montantsNoteHorsSortie($note)['totalDeclare'] <= $seuil) {
            return null;
        }

        return sprintf(
            'Plus de %s € : la dépense doit être au budget de la commission, ou acceptée par la trésorerie.',
            (new \NumberFormatter('fr_FR', \NumberFormatter::DECIMAL))->format($seuil),
        );
    }

    /**
     * Numéros (à partir de 1, comme à l'écran) des lignes qui remplissent la condition.
     *
     * @param callable(array<string, mixed>): bool $condition
     *
     * @return list<int>
     */
    private function numerosDesLignes(ExpenseReport $note, callable $condition): array
    {
        $numeros = [];
        foreach ($note->lignes() as $index => $ligne) {
            if ($condition($ligne)) {
                $numeros[] = $index + 1;
            }
        }

        return $numeros;
    }

    /**
     * @param list<int> $numeros
     */
    private function enumerer(array $numeros): string
    {
        $dernier = array_pop($numeros);

        return implode(', ', $numeros) . ' et ' . $dernier;
    }

    /**
     * @param list<MomentDeControle> $moments
     *
     * @return list<array{champ: string, message: string}>
     */
    private function constats(ExpenseReport $note, array $moments, NiveauDeControle $niveau): array
    {
        if (!$note->getType()->estHorsSortie()) {
            return [];
        }

        $constats = [];
        foreach ($this->controles() as $controle) {
            $aCeMoment = array_filter($moments, static fn (MomentDeControle $moment) => \in_array($moment, $controle['moments'], true));
            if ($controle['niveau'] !== $niveau || [] === $aCeMoment) {
                continue;
            }

            $message = $controle['controle']($note);
            if (null !== $message) {
                $constats[] = ['champ' => $controle['champ'], 'message' => $message];
            }
        }

        return $constats;
    }
}
