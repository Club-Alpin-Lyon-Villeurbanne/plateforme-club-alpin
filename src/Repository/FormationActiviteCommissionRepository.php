<?php

namespace App\Repository;

use App\Entity\FormationActiviteCommission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationActiviteCommission>
 */
class FormationActiviteCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationActiviteCommission::class);
    }

    /**
     * Récupère toutes les commissions associées à un code activité FFCAM.
     *
     * @param string $codeActivite Le code activité FFCAM (ex: "SN" pour "SPORTS DE NEIGE")
     *
     * @return array<Commission>
     */
    public function findCommissionsByCodeActivite(string $codeActivite): array
    {
        return $this->createQueryBuilder('fac')
            ->select('c')
            ->innerJoin('fac.commission', 'c')
            ->where('fac.codeActivite = :codeActivite')
            ->setParameter('codeActivite', $codeActivite)
            ->orderBy('c.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les codes activités FFCAM associés à une commission.
     *
     * @param Commission $commission
     *
     * @return array<string>
     */
    public function findCodesActiviteByCommission($commission): array
    {
        $results = $this->createQueryBuilder('fac')
            ->select('fac.codeActivite')
            ->where('fac.commission = :commission')
            ->setParameter('commission', $commission)
            ->getQuery()
            ->getResult();

        return array_column($results, 'codeActivite');
    }
}
