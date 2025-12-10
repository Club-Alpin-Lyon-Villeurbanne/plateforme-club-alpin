<?php

namespace App\Trait;

use Doctrine\ORM\QueryBuilder;

trait PaginationTrait
{
    protected function getPaginatedResults(QueryBuilder $qb, int $first, int $perPage): array
    {
        return $qb->setFirstResult($first)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }
}
