<?php

namespace App\Service\NoteDeFrais;

use App\Entity\User;
use App\Repository\ExpenseReportRepository;
use App\Security\Voter\ExpenseReportVoter;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Les notes hors sortie que l'utilisateur connecté peut traiter : la liste « notes de frais à
 * valider » et le compteur du menu partagent cette seule requête.
 */
class NotesAValider
{
    public function __construct(
        private readonly Security $security,
        private readonly ExpenseReportRepository $notes,
    ) {
    }

    public function concerneLUtilisateur(): bool
    {
        return $this->security->isGranted(ExpenseReportVoter::MANAGE_EXPENSE_REPORTS);
    }

    /**
     * Nombre de notes en attente, ou null si l'utilisateur ne valide aucune note.
     */
    public function nombreEnAttente(): ?int
    {
        if (!$this->concerneLUtilisateur()) {
            return null;
        }

        $requete = $this->notes->createQueryBuilder('note')->select('COUNT(note.id)');
        $this->restreindre($requete, 'note', false);

        return (int) $requete->getQuery()->getSingleScalarResult();
    }

    /**
     * Sans filtre de statut, seules les notes envoyées sont gardées. Un brouillon n'est jamais
     * à valider, et personne ne valide sa propre note.
     */
    public function restreindre(QueryBuilder $requete, string $alias, bool $statutFiltre): void
    {
        $utilisateur = $this->security->getUser();
        if (!$utilisateur instanceof User || !$this->concerneLUtilisateur()) {
            $requete->andWhere('1 = 0');

            return;
        }

        $requete
            ->andWhere(sprintf('%s.type != :a_valider_sortie', $alias))
            ->andWhere(sprintf('%s.status != :a_valider_brouillon', $alias))
            ->andWhere(sprintf('%s.user != :a_valider_valideur', $alias))
            ->setParameter('a_valider_sortie', ExpenseReportTypeEnum::SORTIE->value)
            ->setParameter('a_valider_brouillon', ExpenseReportStatusEnum::DRAFT->value)
            ->setParameter('a_valider_valideur', $utilisateur);

        if (!$statutFiltre) {
            $requete->andWhere(sprintf('%s.status = :a_valider_envoyee', $alias))
                ->setParameter('a_valider_envoyee', ExpenseReportStatusEnum::SUBMITTED->value);
        }
    }
}
