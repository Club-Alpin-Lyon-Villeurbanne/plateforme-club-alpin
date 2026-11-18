<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\IriConverterInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * `SearchFilter` sur la sortie d'une note, sans effet sur les notes hors sortie.
 */
final class RechercheSurLaSortieFilter extends FiltreSurLaSortie
{
    public function __construct(ManagerRegistry $managerRegistry, IriConverterInterface $iriConverter, ?LoggerInterface $logger = null, ?array $properties = null)
    {
        parent::__construct(new SearchFilter($managerRegistry, $iriConverter, null, $logger, $properties));
    }
}
