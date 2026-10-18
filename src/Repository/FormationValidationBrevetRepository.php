<?php

namespace App\Repository;

use App\Entity\FormationReferentielBrevet;
use App\Entity\FormationValidationBrevet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FormationValidationBrevet|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormationValidationBrevet|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormationValidationBrevet[]    findAll()
 * @method FormationValidationBrevet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationValidationBrevetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationValidationBrevet::class);
    }

    /**
     * Date affichée de chaque brevet détenu, par code de brevet puis par id d'adhérent :
     * formation continue, sinon recyclage, sinon obtention.
     *
     * @param User[]                       $users
     * @param FormationReferentielBrevet[] $brevets
     *
     * @return array<string, array<int, ?string>>
     */
    public function getDatesByCodeAndUser(array $users, array $brevets): array
    {
        if (!$users || !$brevets) {
            return [];
        }

        /** @var FormationValidationBrevet[] $validations */
        $validations = $this->createQueryBuilder('v')
            ->addSelect('b')
            ->innerJoin('v.brevet', 'b')
            ->where('v.user IN (:users)')
            ->andWhere('v.brevet IN (:brevets)')
            ->setParameter('users', $users)
            ->setParameter('brevets', $brevets)
            ->getQuery()
            ->getResult()
        ;

        $dates = [];
        foreach ($validations as $validation) {
            $date = $validation->getDateFormationContinue() ?? $validation->getDateRecyclage() ?? $validation->getDateObtention();
            $dates[$validation->getBrevet()->getCodeBrevet()][$validation->getUser()->getId()] = $date?->format('d/m/Y');
        }

        return $dates;
    }

    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('b')
            ->delete()
            ->where('b.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;
    }
}
