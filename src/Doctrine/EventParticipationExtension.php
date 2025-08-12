<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Article;
use App\Entity\EventParticipation;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Google\Service\AIPlatformNotebooks\Event;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class EventParticipationExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private Security $security) {}

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (EventParticipation::class !== $resourceClass) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        // Ensure that the user is part of the event participation with the required roles
        $queryBuilder->where(
            $queryBuilder->expr()->exists(sprintf('select p2.id from App:EventParticipation p2 where p2.evt = %s.evt and p2.user = :user and p2.role in (:roles)', $rootAlias))
        )->setParameter('roles', EventParticipation::ROLES_ENCADREMENT)
          ->setParameter('user', $this->security->getUser());
    }
}
