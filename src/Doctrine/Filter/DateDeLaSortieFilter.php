<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * `DateFilter` sur la sortie d'une note, sans effet sur les notes hors sortie.
 */
final class DateDeLaSortieFilter extends FiltreSurLaSortie
{
    public function __construct(ManagerRegistry $managerRegistry, ?LoggerInterface $logger = null, ?array $properties = null)
    {
        parent::__construct(new DateFilter($managerRegistry, $logger, $properties));
    }
}
