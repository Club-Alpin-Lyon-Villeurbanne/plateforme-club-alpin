<?php

namespace App\Service\NoteDeFrais;

use App\Entity\User;
use App\Repository\ExpenseReportRepository;
use App\Security\Voter\ExpenseReportVoter;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Les notes hors sortie que l'utilisateur connecté peut traiter : la liste « notes de frais à
 * valider » et le compteur du menu partagent cette seule requête. Un gestionnaire les voit
 * toutes ; un responsable, celles des commissions dont il valide les notes.
 */
class NotesAValider
{
    public function __construct(
        private readonly Security $security,
        private readonly ExpenseReportRepository $notes,
        private readonly ValideursNoteHorsSortie $valideurs,
        #[Autowire(env: 'bool:DISPLAY_NOTES_DE_FRAIS')]
        private readonly bool $notesDeFraisActives,
    ) {
    }

    public function concerneLUtilisateur(): bool
    {
        $utilisateur = $this->security->getUser();

        return $this->security->isGranted(ExpenseReportVoter::MANAGE_EXPENSE_REPORTS)
            || ($utilisateur instanceof User && [] !== $this->valideurs->commissionsValideesPar($utilisateur));
    }

    /**
     * Nombre de notes en attente, pour le menu et la pastille de l'en-tête ; null si
     * l'utilisateur ne valide aucune note ou si le club n'utilise pas les notes de frais.
     */
    public function nombreEnAttente(): ?int
    {
        if (!$this->notesDeFraisActives || !$this->concerneLUtilisateur()) {
            return null;
        }

        $requete = $this->notes->createQueryBuilder('note')->select('COUNT(note.id)');
        $this->restreindre($requete, 'note');

        return (int) $requete->getQuery()->getSingleScalarResult();
    }

    /**
     * Sans filtre de statut, seules les notes envoyées sont gardées. Un brouillon n'est jamais
     * à valider, et personne ne valide sa propre note.
     *
     * @param array<string, mixed> $filtres filtres de la requête d'API
     */
    public function restreindre(QueryBuilder $requete, string $alias, array $filtres = []): void
    {
        $utilisateur = $this->security->getUser();
        if (!$utilisateur instanceof User) {
            $requete->andWhere('1 = 0');

            return;
        }

        if (!$this->security->isGranted(ExpenseReportVoter::MANAGE_EXPENSE_REPORTS)) {
            $commissions = $this->valideurs->commissionsValideesPar($utilisateur);
            // Trier par le choix entre don et remboursement le dévoilerait au responsable.
            if ([] === $commissions || isset($filtres['refundRequired'])) {
                $requete->andWhere('1 = 0');

                return;
            }
            $requete->andWhere(sprintf('%s.commission IN (:a_valider_commissions)', $alias))
                ->setParameter('a_valider_commissions', $commissions);
        }

        $requete
            ->andWhere(sprintf('%s.type != :a_valider_sortie', $alias))
            ->andWhere(sprintf('%s.status != :a_valider_brouillon', $alias))
            ->andWhere(sprintf('%s.user != :a_valider_valideur', $alias))
            ->setParameter('a_valider_sortie', ExpenseReportTypeEnum::SORTIE->value)
            ->setParameter('a_valider_brouillon', ExpenseReportStatusEnum::DRAFT->value)
            ->setParameter('a_valider_valideur', $utilisateur);

        if (!isset($filtres['status'])) {
            $requete->andWhere(sprintf('%s.status = :a_valider_envoyee', $alias))
                ->setParameter('a_valider_envoyee', ExpenseReportStatusEnum::SUBMITTED->value);
        }
    }
}
