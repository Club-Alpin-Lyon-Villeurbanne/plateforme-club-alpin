<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryAccueilTest extends KernelTestCase
{
    // cafnum synthétiques absents du seed : le test reste isolé et ne détruit
    // aucune donnée de référence partagée (la base de test n'a pas de rollback transactionnel).
    // cafnum_user est limité à 20 caractères
    private const CAFNUM_ELIGIBLE = 'TEST-ACC-ELIGIBLE';
    private const CAFNUM_DOUBLON = 'TEST-ACC-DOUBLON';
    private const SEASON = 2026;

    private EntityManagerInterface $em;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(UserRepository::class);

        // idempotent : on ne nettoie que nos propres adhérents synthétiques
        $this->em->createQuery('DELETE FROM ' . User::class . ' u WHERE u.cafnum IN (:cafnums)')
            ->setParameter('cafnums', [self::CAFNUM_ELIGIBLE, self::CAFNUM_DOUBLON])
            ->execute();

        $joinDate = new \DateTimeImmutable(self::SEASON . '-09-15 00:00:00');

        // remplit les six critères
        $this->persistUser(self::CAFNUM_ELIGIBLE, 'eligible.accueil@test-accueil.example', $joinDate);
        // viole un seul critère : email préfixé doublon. (adresse non routable, dédup FFCAM)
        $this->persistUser(self::CAFNUM_DOUBLON, 'doublon.accueil@test-accueil.example', $joinDate);

        $this->em->flush();
    }

    public function testLaRequeteDeSelectionAppliqueTousLesFiltres(): void
    {
        $dql = UserRepository::ACCUEIL_CIRCUIT_DQL;

        $this->assertStringContainsString('u.isDeleted = false', $dql);
        $this->assertStringContainsString('u.profileType = ' . User::PROFILE_CLUB_MEMBER, $dql);
        $this->assertStringContainsString('u.joinDate >= :seasonStart', $dql);
        $this->assertStringContainsString('u.radiationDate IS NULL', $dql);
        $this->assertStringContainsString('u.accueilSeason < :season', $dql);
        $this->assertStringContainsString("u.email NOT LIKE 'doublon.%'", $dql);
    }

    public function testFindForAccueilCircuitEcarteLAdherentAvecEmailDoublon(): void
    {
        $eligible = $this->repository->findOneBy(['cafnum' => self::CAFNUM_ELIGIBLE]);
        $doublon = $this->repository->findOneBy(['cafnum' => self::CAFNUM_DOUBLON]);

        $ids = array_map(fn (User $u) => $u->getId(), $this->repository->findForAccueilCircuit(self::SEASON));

        $this->assertContains($eligible->getId(), $ids, 'un adhérent qui remplit les six critères doit être sélectionné');
        $this->assertNotContains($doublon->getId(), $ids, "un email préfixé doublon. n'est pas routable et doit être écarté");
    }

    public function testMarkAccueilSeasonRendLAdherentInvisibleEtIncrementeLeCompteur(): void
    {
        $eligible = $this->repository->findOneBy(['cafnum' => self::CAFNUM_ELIGIBLE]);
        $countAvant = $this->repository->countAccueilForSeason(self::SEASON);

        $this->repository->markAccueilSeason([$eligible->getId()], self::SEASON);

        $this->assertSame($countAvant + 1, $this->repository->countAccueilForSeason(self::SEASON));

        $idsApres = array_map(fn (User $u) => $u->getId(), $this->repository->findForAccueilCircuit(self::SEASON));
        $this->assertNotContains($eligible->getId(), $idsApres, 'un adhérent déjà marqué pour la saison ne doit plus ressortir');
    }

    private function persistUser(string $cafnum, string $email, \DateTimeImmutable $joinDate): void
    {
        $user = (new User())
            ->setCafnum($cafnum)
            ->setFirstname('Test')
            ->setLastname('Accueil')
            ->setNickname($cafnum)
            ->setEmail($email)
            ->setProfileType(User::PROFILE_CLUB_MEMBER)
            ->setJoinDate($joinDate)
        ;
        $this->em->persist($user);
    }
}
