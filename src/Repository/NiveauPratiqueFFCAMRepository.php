<?php

namespace App\Repository;

use App\Entity\NiveauPratiqueFFCAM;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NiveauPratiqueFFCAM>
 */
class NiveauPratiqueFFCAMRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NiveauPratiqueFFCAM::class);
    }

    /**
     * Trouve les niveaux de pratique par adhérent
     */
    public function findByAdherent(User $adherent): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.adherent = :adherent')
            ->setParameter('adherent', $adherent)
            ->orderBy('n.activite', 'ASC')
            ->addOrderBy('n.niveauPratique', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les niveaux de pratique par numéro de licence FFCAM
     */
    public function findByNumeroLicence(string $numeroLicence): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.adherent', 'a')
            ->andWhere('a.cafnum = :numeroLicence')
            ->setParameter('numeroLicence', $numeroLicence)
            ->orderBy('n.activite', 'ASC')
            ->addOrderBy('n.niveauPratique', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un niveau de pratique par adhérent et activité (pour éviter les doublons)
     */
    public function findByAdherentAndActivite(User $adherent, string $activite, string $nomActivite): ?NiveauPratiqueFFCAM
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.adherent = :adherent')
            ->andWhere('n.activite = :activite')
            ->andWhere('n.nomActivite = :nomActivite')
            ->setParameter('adherent', $adherent)
            ->setParameter('activite', $activite)
            ->setParameter('nomActivite', $nomActivite)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les niveaux de pratique groupés par activité pour un adhérent
     */
    public function findByAdherentGroupeParActivite(User $adherent): array
    {
        $resultats = $this->findByAdherent($adherent);
        $groupe = [];

        foreach ($resultats as $niveauPratique) {
            $activite = $niveauPratique->getActivite();
            if (!isset($groupe[$activite])) {
                $groupe[$activite] = [];
            }
            $groupe[$activite][] = $niveauPratique;
        }

        return $groupe;
    }

    /**
     * Obtient les statistiques par activité et niveau
     */
    public function getStatistiquesActivites(): array
    {
        return $this->createQueryBuilder('n')
            ->select('n.activite, n.niveauPratique, COUNT(n.id) as nombre')
            ->groupBy('n.activite', 'n.niveauPratique')
            ->orderBy('n.activite', 'ASC')
            ->addOrderBy('n.niveauPratique', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les adhérents par activité et niveau
     */
    public function findAdherentsByActiviteAndNiveau(string $activite, ?string $niveau = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->select('DISTINCT a')
            ->join('n.adherent', 'a')
            ->where('n.activite = :activite')
            ->setParameter('activite', $activite);

        if ($niveau !== null) {
            $qb->andWhere('n.niveauPratique = :niveau')
               ->setParameter('niveau', $niveau);
        }

        return $qb->orderBy('a.nom', 'ASC')
                  ->addOrderBy('a.prenom', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}