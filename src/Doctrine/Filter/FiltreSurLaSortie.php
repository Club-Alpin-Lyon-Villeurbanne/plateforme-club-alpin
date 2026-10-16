<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Doctrine\ORM\QueryBuilder;

/**
 * Filtre d'une note sur sa sortie (`event.*`) qui ne porte que sur les notes de sortie : une note
 * hors sortie n'en sort pas. Le filtre d'API Platform s'applique tel quel aux notes de sortie,
 * dans une sous-requête.
 */
abstract class FiltreSurLaSortie implements FilterInterface
{
    public function __construct(
        private readonly FilterInterface $filtre,
    ) {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $note = $queryNameGenerator->generateJoinAlias('note_de_sortie');
        $notesDeSortie = $queryBuilder->getEntityManager()->createQueryBuilder()->select(sprintf('%s.id', $note))->from($resourceClass, $note);
        $this->filtre->apply($notesDeSortie, $queryNameGenerator, $resourceClass, $operation, $context);
        // Filtre absent de la requête, ou valeur que le filtre d'origine ignore.
        if (null === $notesDeSortie->getDQLPart('where')) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $sortie = $queryNameGenerator->generateParameterName('type_sortie');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(
                sprintf('%s.type != :%s', $alias, $sortie),
                sprintf('%s.id IN (%s)', $alias, $notesDeSortie->getDQL()),
            ))
            ->setParameter($sortie, ExpenseReportTypeEnum::SORTIE->value);
        foreach ($notesDeSortie->getParameters() as $parametre) {
            $queryBuilder->setParameter($parametre->getName(), $parametre->getValue(), $parametre->getType());
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return $this->filtre->getDescription($resourceClass);
    }
}
