<?php

namespace App\Tests\Repository;

use App\Entity\Comment;
use App\Entity\MediaUpload;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryAnonymisationTest extends KernelTestCase
{
    private const CAFNUM_SANS_ACTIVITE = 'TEST-ANON-SANS-ACT';
    private const CAFNUM_AVEC_ACTIVITE = 'TEST-ANON-AVEC-ACT';
    private const CAFNUM_SUPPRIME = 'TEST-ANON-SUPPRIME';
    private const CAFNUM_PHOTO = 'TEST-ANON-PHOTO';
    private const COUPURE = '2024-08-31 23:59:59';

    private EntityManagerInterface $em;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(UserRepository::class);

        $this->supprimerLesComptesDeTest();

        $this->persistUser(self::CAFNUM_SANS_ACTIVITE);

        $avecActivite = $this->persistUser(self::CAFNUM_AVEC_ACTIVITE);
        $this->em->persist((new Comment())
            ->setUser($avecActivite)
            ->setName('Test')
            ->setEmail('commentaire@test-anonymisation.example')
            ->setCont('Commentaire de test')
            ->setParentType(Comment::ARTICLE_TYPE)
            ->setParent(0)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime()));

        $this->persistUser(self::CAFNUM_SUPPRIME)->setIsDeleted(true);

        $avecPhoto = $this->persistUser(self::CAFNUM_PHOTO);
        $photo = (new MediaUpload())
            ->setFilename('photo-test-anonymisation.jpg')
            ->setUploadedBy($avecPhoto)
            ->setUsed(true);
        $this->em->persist($photo);
        $avecPhoto->setProfilePicture($photo);

        $this->em->flush();
    }

    public function testUnCompteSansActiviteDejaAnonymiseNeRessortPlus(): void
    {
        $user = $this->getUser(self::CAFNUM_SANS_ACTIVITE);

        $this->assertContains($user->getId(), $this->idsSansActivite(), 'un adhérent sous la coupure et sans activité doit être sélectionné');

        $this->repository->anonymizeUser($user);
        $this->em->clear();

        $this->assertNotContains($user->getId(), $this->idsSansActivite(), 'un compte déjà anonymisé ne doit pas être retraité le lendemain');
    }

    public function testUnCompteAvecActiviteDejaAnonymiseNeRessortPlus(): void
    {
        $user = $this->getUser(self::CAFNUM_AVEC_ACTIVITE);

        $this->assertContains($user->getId(), $this->idsAvecActivite(), 'un adhérent sous la coupure avec un commentaire doit être sélectionné');
        $this->assertNotContains($user->getId(), $this->idsSansActivite());

        $this->repository->anonymizeUser($user);
        $this->em->clear();

        $this->assertNotContains($user->getId(), $this->idsAvecActivite(), 'un compte déjà anonymisé ne doit pas être retraité le lendemain');
    }

    public function testUnCompteSupprimeSansAvoirEteAnonymiseResteEligible(): void
    {
        $user = $this->getUser(self::CAFNUM_SUPPRIME);
        $this->assertTrue($user->isDeleted());

        $this->assertContains($user->getId(), $this->idsSansActivite(), 'la suppression admin et la fusion de doublons posent isDeleted sans anonymiser : le compte doit rester éligible');

        $this->repository->anonymizeUser($user);
        $this->em->clear();

        $this->assertNotContains($user->getId(), $this->idsSansActivite());

        // la synchro FFCAM réécrit le prénom d'une fiche retrouvée par son numéro de licence
        $this->getUser(self::CAFNUM_SUPPRIME)->setFirstname('Jean');
        $this->em->flush();
        $this->em->clear();

        $this->assertContains($user->getId(), $this->idsSansActivite(), 'une fiche anonymisée puis réécrite doit être réanonymisée');
    }

    public function testLAnonymisationEffaceLesDonneesPersonnellesEtLaReferenceALaPhoto(): void
    {
        $user = $this->getUser(self::CAFNUM_PHOTO);
        $id = $user->getId();

        $this->assertNotNull($user->getProfilePicture());
        $this->assertNotNull($user->getEmail());
        $this->assertNotNull($user->getTel());
        $this->assertNotNull($user->getAdresse());

        $this->repository->anonymizeUser($user);
        $this->em->clear();

        $anonymise = $this->repository->find($id);

        $this->assertTrue($anonymise->isDeleted());
        $this->assertSame(ucfirst(UserRepository::PRENOM_ANONYMISE), $anonymise->getFirstname());
        $this->assertNull($anonymise->getEmail());
        $this->assertNull($anonymise->getTel());
        $this->assertNull($anonymise->getAdresse());
        $this->assertNull($anonymise->getProfilePicture(), 'la référence à la photo de profil doit être effacée');
    }

    private function persistUser(string $cafnum): User
    {
        $user = (new User())
            ->setCafnum($cafnum)
            ->setFirstname('Test')
            ->setLastname('Anonymisation')
            ->setNickname($cafnum)
            ->setEmail(strtolower($cafnum) . '@test-anonymisation.example')
            ->setTel('0600000000')
            ->setAdresse('1 rue du Test')
            ->setProfileType(User::PROFILE_CLUB_MEMBER)
            ->setJoinDate(new \DateTimeImmutable('2022-09-15 00:00:00'))
        ;

        $this->em->persist($user);

        return $user;
    }

    private function getUser(string $cafnum): User
    {
        return $this->repository->findOneBy(['cafnum' => $cafnum]);
    }

    /** @return int[] */
    private function idsSansActivite(): array
    {
        return array_map(fn (User $u) => $u->getId(), $this->repository->findUsersWithoutActivity(new \DateTime(self::COUPURE)));
    }

    /** @return int[] */
    private function idsAvecActivite(): array
    {
        return array_map(fn (User $u) => $u->getId(), $this->repository->findUsersWithActivity(new \DateTime(self::COUPURE)));
    }

    private function supprimerLesComptesDeTest(): void
    {
        $ids = $this->em->createQuery('SELECT u.id FROM ' . User::class . ' u WHERE u.cafnum IN (:cafnums)')
            ->setParameter('cafnums', [self::CAFNUM_SANS_ACTIVITE, self::CAFNUM_AVEC_ACTIVITE, self::CAFNUM_SUPPRIME, self::CAFNUM_PHOTO])
            ->getSingleColumnResult();

        if ([] === $ids) {
            return;
        }

        $this->em->createQuery('DELETE FROM ' . Comment::class . ' c WHERE c.user IN (:ids)')->setParameter('ids', $ids)->execute();
        $this->em->createQuery('UPDATE ' . User::class . ' u SET u.profilePicture = NULL WHERE u.id IN (:ids)')->setParameter('ids', $ids)->execute();
        $this->em->createQuery('DELETE FROM ' . MediaUpload::class . ' m WHERE m.uploadedBy IN (:ids)')->setParameter('ids', $ids)->execute();
        $this->em->createQuery('DELETE FROM ' . User::class . ' u WHERE u.id IN (:ids)')->setParameter('ids', $ids)->execute();
    }
}
