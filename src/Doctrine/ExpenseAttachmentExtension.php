<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ExpenseAttachment;
use App\Entity\ExpenseReport;
use App\Security\Voter\ExpenseReportVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Justificatifs d'une note de frais : la liste est toujours celle d'une note, visible par ceux qui y ont accès.
 */
final class ExpenseAttachmentExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (ExpenseAttachment::class !== $resourceClass) {
            return;
        }

        $reportId = $context['uri_variables']['expenseReportId'] ?? null;
        $report = null === $reportId ? null : $this->entityManager->find(ExpenseReport::class, $reportId);

        if (null === $report || !$this->security->isGranted(ExpenseReportVoter::ACCESS_EXPENSE_REPORT, $report)) {
            $queryBuilder->andWhere('1 = 0');
        }
    }
}
