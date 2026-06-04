<?php

namespace App\Tests\Repository;

use App\Entity\Commune;
use App\Repository\CommuneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommuneRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CommuneRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(CommuneRepository::class);

        // jeu de données isolé : deux hameaux homonymes (même CP) + une autre commune
        $this->em->createQuery('DELETE FROM ' . Commune::class . ' c WHERE c.codePostal IN (:cp)')
            ->setParameter('cp', ['74400', '69510'])
            ->execute();

        $this->persistCommune('74056', 'Chamonix-Mont-Blanc', '74400', null, 45.92375, 6.86861);
        $this->persistCommune('74056', 'Chamonix-Mont-Blanc', '74400', 'ARGENTIERE', 45.96806, 6.92694);
        $this->persistCommune('69133', 'Messimy', '69510', null, 45.71667, 4.71667);
        $this->em->flush();
    }

    public function testMatchesLabelWithoutLigne5(): void
    {
        $commune = $this->repository->findOneByLabel('69510 Messimy');

        $this->assertNotNull($commune);
        $this->assertSame('Messimy', $commune->getNomCommune());
    }

    public function testMatchesLabelWithLigne5(): void
    {
        $commune = $this->repository->findOneByLabel('74400 Chamonix-Mont-Blanc (ARGENTIERE)');

        $this->assertNotNull($commune);
        $this->assertSame('ARGENTIERE', $commune->getLigne5());
        // les coordonnées renvoyées sont bien celles du hameau, pas de la commune principale
        $this->assertEqualsWithDelta(45.96806, (float) $commune->getLatitude(), 0.00001);
    }

    public function testDistinguishesHomonymsBySuffix(): void
    {
        $principale = $this->repository->findOneByLabel('74400 Chamonix-Mont-Blanc');

        $this->assertNotNull($principale);
        // pas de suffixe de hameau → c'est bien la commune principale, pas un hameau
        $this->assertSame('74400 Chamonix-Mont-Blanc', $principale->getLabel());
        $this->assertEqualsWithDelta(45.92375, (float) $principale->getLatitude(), 0.00001);
    }

    public function testIsCaseInsensitive(): void
    {
        $this->assertNotNull($this->repository->findOneByLabel('69510 MESSIMY'));
        $this->assertNotNull($this->repository->findOneByLabel('  69510 messimy  '));
    }

    public function testReturnsNullWithoutPostalCode(): void
    {
        $this->assertNull($this->repository->findOneByLabel('Messimy'));
        $this->assertNull($this->repository->findOneByLabel(''));
    }

    public function testReturnsNullWhenNoMatch(): void
    {
        $this->assertNull($this->repository->findOneByLabel('69510 Ailleurs'));
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
