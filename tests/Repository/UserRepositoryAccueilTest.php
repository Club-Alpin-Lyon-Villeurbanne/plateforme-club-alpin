<?php

namespace App\Tests\Repository;

use App\Entity\AccueilCircuitEnum;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryAccueilTest extends KernelTestCase
{
    private const CAFNUM_ELIGIBLE = 'TEST-ACC-ELIGIBLE';
    private const CAFNUM_DOUBLON = 'TEST-ACC-DOUBLON';
    private const CAFNUM_RENOUVELANT = 'TEST-ACC-RENOUVELANT';
    private const SEASON = 2026;

    private EntityManagerInterface $em;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(UserRepository::class);

        $this->em->createQuery('DELETE FROM ' . User::class . ' u WHERE u.cafnum IN (:cafnums)')
            ->setParameter('cafnums', [self::CAFNUM_ELIGIBLE, self::CAFNUM_DOUBLON, self::CAFNUM_RENOUVELANT])
            ->execute();

        $joinDate = new \DateTimeImmutable(self::SEASON . '-09-15 00:00:00');

        $this->persistUser(self::CAFNUM_ELIGIBLE, 'eligible.accueil@test-accueil.example', $joinDate);
        $this->persistUser(self::CAFNUM_DOUBLON, 'doublon.accueil@test-accueil.example', $joinDate);
        $this->persistUser(self::CAFNUM_RENOUVELANT, 'renouvelant.accueil@test-accueil.example', $joinDate, new \DateTime('2019-03-04'));

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

        $ids = array_map(fn (User $u) => $u->getId(), $this->repository->findForAccueilCircuit(self::SEASON, AccueilCircuitEnum::NOUVEAUX));

        $this->assertContains($eligible->getId(), $ids, 'un adhérent qui remplit les six critères doit être sélectionné');
        $this->assertNotContains($doublon->getId(), $ids, "un email préfixé doublon. n'est pas routable et doit être écarté");

        $renouvelant = $this->repository->findOneBy(['cafnum' => self::CAFNUM_RENOUVELANT]);
        $renouvellements = array_map(fn (User $u) => $u->getId(), $this->repository->findForAccueilCircuit(self::SEASON, AccueilCircuitEnum::RENOUVELLEMENTS));

        $this->assertNotContains($renouvelant->getId(), $ids, 'une fiche antérieure à la saison est un renouvellement, pas un nouvel adhérent');
        $this->assertContains($renouvelant->getId(), $renouvellements);
        $this->assertNotContains($eligible->getId(), $renouvellements, 'une fiche créée pendant la saison est un nouvel adhérent, pas un renouvellement');
    }

    public function testMarkAccueilSeasonRendLAdherentInvisibleEtIncrementeLeCompteur(): void
    {
        $eligible = $this->repository->findOneBy(['cafnum' => self::CAFNUM_ELIGIBLE]);
        $nouveauxAvant = $this->repository->countAccueilForSeason(self::SEASON, AccueilCircuitEnum::NOUVEAUX);
        $renouvellementsAvant = $this->repository->countAccueilForSeason(self::SEASON, AccueilCircuitEnum::RENOUVELLEMENTS);

        $this->repository->markAccueilSeason([$eligible->getId()], self::SEASON);

        $this->assertSame($nouveauxAvant + 1, $this->repository->countAccueilForSeason(self::SEASON, AccueilCircuitEnum::NOUVEAUX));
        $this->assertSame($renouvellementsAvant, $this->repository->countAccueilForSeason(self::SEASON, AccueilCircuitEnum::RENOUVELLEMENTS), 'le compteur du circuit renouvellements ne doit pas bouger');

        $renouvelant = $this->repository->findOneBy(['cafnum' => self::CAFNUM_RENOUVELANT]);
        $this->repository->markAccueilSeason([$renouvelant->getId()], self::SEASON);

        $this->assertSame($renouvellementsAvant + 1, $this->repository->countAccueilForSeason(self::SEASON, AccueilCircuitEnum::RENOUVELLEMENTS));

        $idsApres = array_map(fn (User $u) => $u->getId(), $this->repository->findForAccueilCircuit(self::SEASON, AccueilCircuitEnum::NOUVEAUX));
        $this->assertNotContains($eligible->getId(), $idsApres, 'un adhérent déjà marqué pour la saison ne doit plus ressortir');
    }

    private function persistUser(string $cafnum, string $email, \DateTimeImmutable $joinDate, ?\DateTime $createdAt = null): void
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

        if (null !== $createdAt) {
            $user->setCreatedAt($createdAt);
        }

        $this->em->persist($user);
    }
}
