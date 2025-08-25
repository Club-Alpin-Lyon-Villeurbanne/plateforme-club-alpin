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
        if ($resourceClass !== ExpenseReport::class) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $canValidateReport = $this->security->isGranted('validate_expense_report');
        
        // Filtrer par utilisateur si pas les droits de validation
        if (!$canValidateReport) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias))
                ->setParameter('current_user', $this->security->getUser());
        }

        // Exclure les brouillons sauf si include_drafts=true
        $filters = $context['filters'] ?? [];
        $includeDrafts = isset($filters['include_drafts']) && 'true' === $filters['include_drafts'];
        
        if (!$includeDrafts) {
            $queryBuilder->andWhere(sprintf('%s.status != :draft_status', $rootAlias))
                ->setParameter('draft_status', ExpenseReportStatusEnum::DRAFT->value);
        }
    }
}