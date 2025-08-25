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
 * Applique les filtres de sécurité sur les notes de frais :
 * - Filtre par utilisateur (sauf pour les validateurs)
 * - Exclut les brouillons par défaut (sauf si include_drafts=true).
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
        Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass, $context);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass, $context);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass, array $context): void
    {
        if (ExpenseReport::class !== $resourceClass) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $canValidateReport = $this->security->isGranted('validate_expense_report');

        // 1. Toujours filtrer par utilisateur (sauf pour les validateurs)
        if (!$canValidateReport) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias))
                ->setParameter('current_user', $this->security->getUser());
        }

        // 2. Pour les collections uniquement : exclure les brouillons par défaut
        $isCollectionOperation = ($context['operation'] ?? null) instanceof \ApiPlatform\Metadata\GetCollection;
        if (!$isCollectionOperation) {
            return; // Pas de filtre sur le statut pour GET/PATCH/DELETE d'un item spécifique
        }

        // 3. Permettre d'inclure les brouillons avec ?inclure_brouillons=true
        $filters = $context['filters'] ?? [];
        $includeDrafts = isset($filters['inclure_brouillons']) && 'true' === $filters['inclure_brouillons'];

        if (!$includeDrafts) {
            $queryBuilder->andWhere(sprintf('%s.status != :draft_status', $rootAlias))
                ->setParameter('draft_status', ExpenseReportStatusEnum::DRAFT->value);
        }
    }
}
