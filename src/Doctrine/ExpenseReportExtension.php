<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Gère le filtrage et l'optimisation des notes de frais :
 * - Par défaut : filtré par l'utilisateur connecté (sécurité par défaut)
 * - /admin/notes-de-frais : pas de filtrage (réservé aux admins/gestionnaires via security)
 * - Exclut les brouillons par défaut (sauf si inclure_brouillons=true)
 * - Eager loading des relations pour éviter le problème N+1
 * - Tri par défaut : created_at DESC
 */
final class ExpenseReportExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security
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

        // 1. Filtrage par utilisateur (sauf sur /admin/notes-de-frais)
        if ('/admin/notes-de-frais' !== $operation?->getUriTemplate() && $user) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias))
                ->setParameter('current_user', $user);
        }

        // 2. Exclure les brouillons par défaut (sauf si inclure_brouillons=true)
        $filters = $context['filters'] ?? [];
        $includeDrafts = isset($filters['inclure_brouillons']) && 'true' === $filters['inclure_brouillons'];

        if (!$includeDrafts) {
            $queryBuilder->andWhere(sprintf('%s.status != :draft_status', $rootAlias))
                ->setParameter('draft_status', ExpenseReportStatusEnum::DRAFT->value);
        }

        // 3. Tri par défaut : plus récentes en premier
        // Les index créés dans la migration optimiseront ce tri
        $queryBuilder->orderBy(sprintf('%s.createdAt', $rootAlias), 'DESC');
    }
}
