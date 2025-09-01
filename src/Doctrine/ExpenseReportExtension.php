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
 * - Filtre par utilisateur (sauf pour les admins et validateurs)
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

    private function filterExpenseReports(QueryBuilder $queryBuilder, array $context): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $user = $this->security->getUser();

        // 1. Filtrage par utilisateur (sauf admin et validateurs)
        if ($user 
            && !$this->security->isGranted(SecurityConstants::ROLE_ADMIN)
            && !$this->security->isGranted('validate_expense_report')) {
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

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        // Pour les items individuels, on ne filtre PAS par statut
        // pour permettre l'accès aux brouillons
        // Le filtrage par utilisateur est géré par les annotations de sécurité sur l'entité
    }
}