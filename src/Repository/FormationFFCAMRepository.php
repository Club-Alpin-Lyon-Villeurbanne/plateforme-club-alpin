<?php

namespace App\Repository;

use App\Entity\FormationFFCAM;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationFFCAM>
 */
class FormationFFCAMRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationFFCAM::class);
    }

    /**
     * Trouve les formations par adhérent
     */
    public function findByAdherent(User $adherent): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.adherent = :adherent')
            ->setParameter('adherent', $adherent)
            ->orderBy('f.dateObtention', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les formations par numéro de licence FFCAM
     */
    public function findByNumeroLicence(string $numeroLicence): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.adherent', 'a')
            ->andWhere('a.cafnum = :numeroLicence')
            ->setParameter('numeroLicence', $numeroLicence)
            ->orderBy('f.dateObtention', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une formation par adhérent et code de formation (pour éviter les doublons)
     */
    public function findByAdherentAndCode(User $adherent, string $code): ?FormationFFCAM
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.adherent = :adherent')
            ->andWhere('f.code = :code')
            ->setParameter('adherent', $adherent)
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtient les statistiques des codes de formation
     */
    public function getStatistiquesFormations(): array
    {
        return $this->createQueryBuilder('f')
            ->select('f.code, f.intituleFormation, COUNT(f.id) as nombre')
            ->groupBy('f.code', 'f.intituleFormation')
            ->orderBy('nombre', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les formations par type (code commençant par...)
     */
    public function findByTypeFormation(string $prefixeCode): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.code LIKE :prefixe')
            ->setParameter('prefixe', $prefixeCode . '%')
            ->orderBy('f.dateObtention', 'DESC')
            ->getQuery()
            ->getResult();
    }
}