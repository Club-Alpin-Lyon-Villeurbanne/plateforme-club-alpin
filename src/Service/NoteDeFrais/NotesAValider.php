<?php

namespace App\Service\NoteDeFrais;

use App\Entity\User;
use App\Repository\ExpenseReportRepository;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Les notes hors sortie qu'un responsable peut traiter : celles des commissions dont il valide les
 * notes. La liste « notes de frais à valider » et le compteur du menu partagent cette seule
 * requête. Un gestionnaire n'y a rien de plus : la trésorerie travaille dans compta-club.
 */
class NotesAValider implements ResetInterface
{
    /** @var array<int, int> nombre en attente par utilisateur, le temps d'une requête : l'en-tête le lit deux fois */
    private array $enAttente = [];

    public function __construct(
        private readonly Security $security,
        private readonly ExpenseReportRepository $notes,
        private readonly ValideursNoteHorsSortie $valideurs,
        #[Autowire(env: 'bool:DISPLAY_NOTES_DE_FRAIS')]
        private readonly bool $notesDeFraisActives,
    ) {
    }

    /**
     * Nombre de notes en attente, pour le menu et la pastille de l'en-tête ; null si
     * l'utilisateur ne valide aucune note ou si le club n'utilise pas les notes de frais.
     */
    public function nombreEnAttente(): ?int
    {
        $utilisateur = $this->security->getUser();
        if (!$this->notesDeFraisActives || !$utilisateur instanceof User || [] === $this->valideurs->commissionsValideesPar($utilisateur)) {
            return null;
        }

        if (!\array_key_exists($utilisateur->getId(), $this->enAttente)) {
            $requete = $this->notes->createQueryBuilder('note')->select('COUNT(note.id)');
            $this->restreindre($requete, 'note');
            $this->enAttente[$utilisateur->getId()] = (int) $requete->getQuery()->getSingleScalarResult();
        }

        return $this->enAttente[$utilisateur->getId()];
    }

    public function reset(): void
    {
        $this->enAttente = [];
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
        $commissions = $utilisateur instanceof User ? $this->valideurs->commissionsValideesPar($utilisateur) : [];
        // Trier par le choix entre don et remboursement le dévoilerait au responsable.
        if ([] === $commissions || isset($filtres['refundRequired'])) {
            $requete->andWhere('1 = 0');

            return;
        }

        $requete
            ->andWhere(sprintf('%s.commission IN (:a_valider_commissions)', $alias))
            ->andWhere(sprintf('%s.type != :a_valider_sortie', $alias))
            ->andWhere(sprintf('%s.status != :a_valider_brouillon', $alias))
            ->andWhere(sprintf('%s.user != :a_valider_valideur', $alias))
            ->setParameter('a_valider_commissions', $commissions)
            ->setParameter('a_valider_sortie', ExpenseReportTypeEnum::SORTIE->value)
            ->setParameter('a_valider_brouillon', ExpenseReportStatusEnum::DRAFT->value)
            ->setParameter('a_valider_valideur', $utilisateur);

        if (!isset($filtres['status'])) {
            $requete->andWhere(sprintf('%s.status = :a_valider_envoyee', $alias))
                ->setParameter('a_valider_envoyee', ExpenseReportStatusEnum::SUBMITTED->value);
        }
    }
}
