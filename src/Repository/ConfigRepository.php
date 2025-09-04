<?php

namespace App\Repository;

use App\Entity\Config;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Config|null find($id, $lockMode = null, $lockVersion = null)
 * @method Config|null findOneBy(array $criteria, array $orderBy = null)
 * @method Config[]    findAll()
 * @method Config[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $manager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
        $this->manager = $this->getEntityManager();
    }

    public function getConfigValue(string $code): ?string
    {
        return $this->findOneBy(['code' => $code])?->getValue();
    }

    public function saveConfigValue(string $code, string $value): void
    {
        $existingConfig = $this->findOneBy(['code' => $code]);
        if (!$existingConfig instanceof Config) {
            $existingConfig = new Config();
            $existingConfig->setCode($code);
        }

        $existingConfig->setValue($value);
        $this->manager->persist($existingConfig);
        $this->manager->flush();
    }

    public function removeConfigValue(string $code): void
    {
        $existingConfig = $this->findOneBy(['code' => $code]);
        $this->manager->remove($existingConfig);
        $this->manager->flush();
    }
}
