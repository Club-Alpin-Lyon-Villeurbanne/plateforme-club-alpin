<?php

namespace App\Tests\Repository;

use App\Entity\Commune;
use App\Repository\CommuneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommuneRepositoryTest extends KernelTestCase
{
    // codes postaux synthétiques absents du seed : le test reste isolé et ne détruit
    // aucune donnée de référence partagée (la base de test n'a pas de rollback transactionnel).
    private const CP_HAMEAUX = '99998';
    private const CP_SIMPLE = '99999';

    private EntityManagerInterface $em;
    private CommuneRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(CommuneRepository::class);

        // idempotent : on ne nettoie que nos propres codes postaux synthétiques
        $this->em->createQuery('DELETE FROM ' . Commune::class . ' c WHERE c.codePostal IN (:cp)')
            ->setParameter('cp', [self::CP_HAMEAUX, self::CP_SIMPLE])
            ->execute();

        // deux hameaux homonymes (même CP, ligne5 différent) + une commune simple
        $this->persistCommune('00001', 'Testbourg', self::CP_HAMEAUX, null, 45.10000, 6.10000);
        $this->persistCommune('00001', 'Testbourg', self::CP_HAMEAUX, 'LE HAMEAU HAUT', 45.20000, 6.20000);
        $this->persistCommune('00002', 'Testville', self::CP_SIMPLE, null, 44.50000, 5.50000);
        $this->em->flush();
    }

    public function testMatchesLabelWithoutLigne5(): void
    {
        $commune = $this->repository->findOneByLabel(self::CP_SIMPLE . ' Testville');

        $this->assertNotNull($commune);
        $this->assertSame('Testville', $commune->getNomCommune());
    }

    public function testMatchesLabelWithLigne5(): void
    {
        $commune = $this->repository->findOneByLabel(self::CP_HAMEAUX . ' Testbourg (LE HAMEAU HAUT)');

        $this->assertNotNull($commune);
        $this->assertSame('LE HAMEAU HAUT', $commune->getLigne5());
        // les coordonnées renvoyées sont bien celles du hameau, pas de la commune principale
        $this->assertEqualsWithDelta(45.20000, (float) $commune->getLatitude(), 0.00001);
    }

    public function testDistinguishesHomonymsBySuffix(): void
    {
        $principale = $this->repository->findOneByLabel(self::CP_HAMEAUX . ' Testbourg');

        $this->assertNotNull($principale);
        // pas de suffixe de hameau → c'est bien la commune principale, pas un hameau
        $this->assertSame(self::CP_HAMEAUX . ' Testbourg', $principale->getLabel());
        $this->assertEqualsWithDelta(45.10000, (float) $principale->getLatitude(), 0.00001);
    }

    public function testIsCaseInsensitive(): void
    {
        $this->assertNotNull($this->repository->findOneByLabel(self::CP_SIMPLE . ' TESTVILLE'));
        $this->assertNotNull($this->repository->findOneByLabel('  ' . self::CP_SIMPLE . ' testville  '));
    }

    public function testReturnsNullWithoutPostalCode(): void
    {
        $this->assertNull($this->repository->findOneByLabel('Testville'));
        $this->assertNull($this->repository->findOneByLabel(''));
    }

    public function testReturnsNullWhenNoMatch(): void
    {
        $this->assertNull($this->repository->findOneByLabel(self::CP_SIMPLE . ' Ailleurs'));
        $this->assertNull($this->repository->findOneByLabel('00000 Nulle Part'));
    }

    private function persistCommune(string $insee, string $nom, string $cp, ?string $ligne5, float $lat, float $long): void
    {
        $commune = (new Commune())
            ->setCodeCommuneInsee($insee)
            ->setNomCommune($nom)
            ->setCodePostal($cp)
            ->setLibelleAcheminement(mb_strtoupper($nom))
            ->setLatitude($lat)
            ->setLongitude($long);
        if (null !== $ligne5) {
            $commune->setLigne5($ligne5);
        }
        $this->em->persist($commune);
    }
}
