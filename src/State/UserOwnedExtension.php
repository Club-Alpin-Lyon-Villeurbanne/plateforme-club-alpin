<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ExpenseReport;
use App\Entity\ExpenseReportStatusHistory;
use App\Security\SecurityConstants;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class UserOwnedExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (ExpenseReport::class === $resourceClass) {
            $user = $this->security->getUser();
            if (null === $user || $this->security->isGranted(SecurityConstants::ROLE_ADMIN)) {
                return;
            }
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias))
                ->setParameter('current_user', $user);

            return;
        }
        if (ExpenseReportStatusHistory::class === $resourceClass) {
            $user = $this->security->getUser();
            if (null === $user || $this->security->isGranted(SecurityConstants::ROLE_ADMIN)) {
                return;
            }
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->join(sprintf('%s.expenseReport', $rootAlias), 'er')
                ->andWhere('er.user = :current_user')
                ->setParameter('current_user', $user);

            return;
        }
    }
}
