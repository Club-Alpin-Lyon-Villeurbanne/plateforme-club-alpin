<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ExpenseReport;
use App\Security\SecurityConstants;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Gère le filtrage des notes de frais :
 * - Filtre par utilisateur (sauf pour les admins et gestionnaires de notes de frais)
 * - Exclut les brouillons par défaut (sauf si inclure_brouillons=true)
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

        $this->filterExpenseReports($queryBuilder, $context);
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

    private function filterExpenseReports(QueryBuilder $queryBuilder, array $context): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $user = $this->security->getUser();

        // 1. Filtrage par utilisateur (sauf admin et gestionnaires de notes de frais)
        if ($user
            && !$this->security->isGranted(SecurityConstants::ROLE_ADMIN)
            && !$this->security->isGranted('manage_expense_reports')) {
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
    }
}
