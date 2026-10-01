<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ExpenseAttachment;
use App\Security\Voter\ExpenseReportVoter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Justificatifs d'une note de frais : visibles par le propriétaire de la note et par les gestionnaires.
 */
final class ExpenseAttachmentExtension implements QueryCollectionExtensionInterface
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
        if (ExpenseAttachment::class !== $resourceClass || $this->security->isGranted(ExpenseReportVoter::MANAGE_EXPENSE_REPORTS)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $reportAlias = $queryNameGenerator->generateJoinAlias('expenseReport');
        $userParameter = $queryNameGenerator->generateParameterName('currentUser');

        $queryBuilder
            ->join(sprintf('%s.expenseReport', $rootAlias), $reportAlias)
            ->andWhere(sprintf('%s.user = :%s', $reportAlias, $userParameter))
            ->setParameter($userParameter, $this->security->getUser());
    }
}
