<?php

namespace App\Repository;

use App\Entity\FormationReferentielFormation;
use App\Entity\FormationValidationFormation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationValidationFormation>
 */
class FormationValidationFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationValidationFormation::class);
    }

    /**
     * Date de validation de chaque formation suivie, par code de formation puis par id d'adhérent.
     *
     * @param User[]                          $users
     * @param FormationReferentielFormation[] $formations
     *
     * @return array<string, array<int, ?string>>
     */
    public function getDatesByCodeAndUser(array $users, array $formations): array
    {
        if (!$users || !$formations) {
            return [];
        }

        /** @var FormationValidationFormation[] $validations */
        $validations = $this->createQueryBuilder('v')
            ->addSelect('f')
            ->innerJoin('v.formation', 'f')
            ->where('v.user IN (:users)')
            ->andWhere('v.formation IN (:formations)')
            ->setParameter('users', $users)
            ->setParameter('formations', $formations)
            ->getQuery()
            ->getResult()
        ;

        $dates = [];
        foreach ($validations as $validation) {
            $dates[$validation->getFormation()->getCodeFormation()][$validation->getUser()->getId()] = $validation->getDateValidation()?->format('d/m/Y');
        }

        return $dates;
    }
}
