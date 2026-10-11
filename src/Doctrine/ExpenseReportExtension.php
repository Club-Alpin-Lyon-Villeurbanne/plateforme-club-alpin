<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ExpenseReport;
use App\Service\NoteDeFrais\NotesAValider;
use App\Service\NoteDeFrais\ValideursNoteHorsSortie;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Gère le filtrage et l'optimisation des notes de frais :
 * - Par défaut : filtré par l'utilisateur connecté (sécurité par défaut)
 * - /admin/notes-de-frais : pas de filtrage par utilisateur (réservé aux admins/gestionnaires via security) ;
 *   les notes de sortie, et les notes hors sortie qui attendent la trésorerie ou qu'elle a traitées (compta-club)
 * - /notes-de-frais/a-valider : les notes hors sortie que l'utilisateur valide comme responsable (NotesAValider)
 * - Exclut les brouillons par défaut (sauf si inclure_brouillons=true)
 * - Eager loading des relations pour éviter le problème N+1
 * - Tri par défaut : created_at DESC
 */
final class ExpenseReportExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security,
        private NotesAValider $notesAValider,
        private ValideursNoteHorsSortie $valideurs,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (ExpenseReport::class !== $resourceClass) {
            return;
        }

        $this->filterExpenseReports($queryBuilder, $context, $operation);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        // Pas de filtrage spécifique pour les items individuels
        // Les permissions sont gérées par les voters
    }

    private function filterExpenseReports(QueryBuilder $queryBuilder, array $context, ?Operation $operation): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $user = $this->security->getUser();

        // 0. Eager loading des relations pour éviter N+1 queries
        // Charge user et event en un seul SELECT avec des JOINs
        $queryBuilder
            ->addSelect('user', 'event')
            ->leftJoin(sprintf('%s.user', $rootAlias), 'user')
            ->leftJoin(sprintf('%s.event', $rootAlias), 'event');

        $filters = $context['filters'] ?? [];

        // 1. Filtrage par utilisateur, sauf sur la liste « à valider » et celle de compta-club
        if ('/notes-de-frais/a-valider' === $operation?->getUriTemplate()) {
            $this->notesAValider->restreindre($queryBuilder, $rootAlias, $filters);
        } elseif ('/admin/notes-de-frais' === $operation?->getUriTemplate()) {
            $this->restreindreALaTresorerie($queryBuilder, $rootAlias);
        } elseif ($user) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias))
                ->setParameter('current_user', $user);
        }

        // 2. Exclure les brouillons par défaut (sauf si inclure_brouillons=true)
        $includeDrafts = isset($filters['inclure_brouillons']) && 'true' === $filters['inclure_brouillons'];

        if (!$includeDrafts) {
            $queryBuilder->andWhere(sprintf('%s.status != :draft_status', $rootAlias))
                ->setParameter('draft_status', ExpenseReportStatusEnum::DRAFT->value);
        }

        // 3. Tri par défaut : plus récentes en premier
        // Les index créés dans la migration optimiseront ce tri
        $queryBuilder->orderBy(sprintf('%s.createdAt', $rootAlias), 'DESC');
    }

    /**
     * Une note hors sortie n'intéresse la trésorerie qu'une fois envoyée, et pas tant qu'elle
     * attend un responsable : brouillon exclu même avec `inclure_brouillons`, `submitted`
     * seulement sans valideur.
     */
    private function restreindreALaTresorerie(QueryBuilder $queryBuilder, string $rootAlias): void
    {
        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(
                sprintf('%s.type = :tresorerie_sortie', $rootAlias),
                $queryBuilder->expr()->andX(
                    sprintf('%s.status != :tresorerie_brouillon', $rootAlias),
                    $queryBuilder->expr()->orX(
                        sprintf('%s.status != :tresorerie_envoyee', $rootAlias),
                        $this->valideurs->conditionValideurs($queryBuilder, $rootAlias, false),
                    ),
                ),
            ))
            ->setParameter('tresorerie_sortie', ExpenseReportTypeEnum::SORTIE->value)
            ->setParameter('tresorerie_brouillon', ExpenseReportStatusEnum::DRAFT->value)
            ->setParameter('tresorerie_envoyee', ExpenseReportStatusEnum::SUBMITTED->value);
    }
}
