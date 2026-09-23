<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryAnonymisationTest extends KernelTestCase
{
    private const CAFNUM_SANS_ACTIVITE = 'TEST-ANON-SANS-ACT';
    private const COUPURE = '2024-08-31 23:59:59';

    private EntityManagerInterface $em;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(UserRepository::class);

        $this->em->createQuery('DELETE FROM ' . User::class . ' u WHERE u.cafnum = :cafnum')
            ->setParameter('cafnum', self::CAFNUM_SANS_ACTIVITE)
            ->execute();

        $user = (new User())
            ->setCafnum(self::CAFNUM_SANS_ACTIVITE)
            ->setFirstname('Test')
            ->setLastname('Anonymisation')
            ->setNickname(self::CAFNUM_SANS_ACTIVITE)
            ->setEmail('sans.activite@test-anonymisation.example')
            ->setProfileType(User::PROFILE_CLUB_MEMBER)
            ->setJoinDate(new \DateTimeImmutable('2022-09-15 00:00:00'))
        ;

        $this->em->persist($user);
        $this->em->flush();
    }

    public function testUnCompteDejaAnonymiseNeRessortPlus(): void
    {
        $coupure = new \DateTime(self::COUPURE);
        $user = $this->repository->findOneBy(['cafnum' => self::CAFNUM_SANS_ACTIVITE]);

        $ids = array_map(fn (User $u) => $u->getId(), $this->repository->findUsersWithoutActivity($coupure));
        $this->assertContains($user->getId(), $ids, 'un adhérent sous la coupure et sans activité doit être sélectionné');

        $this->repository->anonymizeUser($user);
        $this->em->clear();

        $idsApres = array_map(fn (User $u) => $u->getId(), $this->repository->findUsersWithoutActivity($coupure));
        $this->assertNotContains($user->getId(), $idsApres, 'un compte déjà anonymisé ne doit pas être retraité le lendemain');

        $avecActivite = array_map(fn (User $u) => $u->getId(), $this->repository->findUsersWithActivity($coupure));
        $this->assertNotContains($user->getId(), $avecActivite);
    }

    public function testLAnonymisationEffaceLesDonneesPersonnellesEtLaPhoto(): void
    {
        $user = $this->repository->findOneBy(['cafnum' => self::CAFNUM_SANS_ACTIVITE]);
        $id = $user->getId();

        $this->repository->anonymizeUser($user);
        $this->em->clear();

        $anonymise = $this->repository->find($id);

        $this->assertTrue($anonymise->isDeleted());
        $this->assertNull($anonymise->getEmail());
        $this->assertNull($anonymise->getTel());
        $this->assertNull($anonymise->getAdresse());
        $this->assertNull($anonymise->getProfilePicture(), 'la référence à la photo de profil doit être effacée');
        $this->assertSame('Compte', $anonymise->getFirstname(), 'le getter capitalise le prénom stocké en minuscule');
    }
}
