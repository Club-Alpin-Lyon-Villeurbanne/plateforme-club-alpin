<?php

namespace App\Tests\Command;

use App\Command\UserAnonymizationCronCommand;
use App\Entity\MediaUpload;
use App\Entity\User;
use App\Repository\FormationValidationBrevetRepository;
use App\Repository\UserAttrRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\UserRepository;
use App\Service\UserLicenseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class UserAnonymizationCronCommandTest extends KernelTestCase
{
    private const CAFNUM_PHOTO_1 = 'TEST-CMD-ANON-1';
    private const CAFNUM_PHOTO_2 = 'TEST-CMD-ANON-2';
    private const CAFNUM_SANS_PHOTO = 'TEST-CMD-ANON-3';

    private EntityManagerInterface $em;
    private Filesystem $filesystem;

    /** @var string[] */
    private array $fichiers = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->filesystem = new Filesystem();

        $this->supprimerLesComptesDeTest();

        $dossier = self::getContainer()->getParameter('public_dir') . '/ftp/uploads/files/';
        foreach ([self::CAFNUM_PHOTO_1, self::CAFNUM_PHOTO_2] as $cafnum) {
            $user = $this->persistUser($cafnum);
            $nomFichier = strtolower($cafnum) . '.jpg';
            $photo = (new MediaUpload())->setFilename($nomFichier)->setUploadedBy($user)->setUsed(true);
            $this->em->persist($photo);
            $user->setProfilePicture($photo);

            $this->fichiers[] = $dossier . $nomFichier;
            $this->filesystem->dumpFile($dossier . $nomFichier, 'photo');
        }
        $this->persistUser(self::CAFNUM_SANS_PHOTO);

        $this->em->flush();
        // comme en prod, les photos seront rechargées sous forme de proxys
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->fichiers);
        parent::tearDown();
    }

    public function testLaCommandeAnonymiseLesComptesEtEffaceLeursPhotos(): void
    {
        $photoIds = $this->em->createQuery('SELECT m.id FROM ' . MediaUpload::class . ' m JOIN m.uploadedBy u WHERE u.cafnum IN (:cafnums)')
            ->setParameter('cafnums', [self::CAFNUM_PHOTO_1, self::CAFNUM_PHOTO_2])
            ->getSingleColumnResult();
        $this->assertCount(2, $photoIds);
        foreach ($this->fichiers as $fichier) {
            $this->assertFileExists($fichier);
        }

        $tester = new CommandTester($this->commandeLimiteeAuxComptesDeTest());
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->em->clear();

        foreach ([self::CAFNUM_PHOTO_1, self::CAFNUM_PHOTO_2, self::CAFNUM_SANS_PHOTO] as $cafnum) {
            $user = $this->em->getRepository(User::class)->findOneBy(['cafnum' => $cafnum]);
            $this->assertTrue($user->isDeleted(), $cafnum);
            $this->assertSame(ucfirst(UserRepository::PRENOM_ANONYMISE), $user->getFirstname(), $cafnum);
            $this->assertNull($user->getEmail(), $cafnum);
            $this->assertNull($user->getProfilePicture(), $cafnum);
        }
        foreach ($photoIds as $photoId) {
            $this->assertNull($this->em->find(MediaUpload::class, $photoId), 'la ligne du média doit être supprimée');
        }
        foreach ($this->fichiers as $fichier) {
            $this->assertFileDoesNotExist($fichier, 'Vich doit effacer le fichier de la photo');
        }
    }

    public function testUnDeuxiemePassageNeRetraiteRien(): void
    {
        (new CommandTester($this->commandeLimiteeAuxComptesDeTest()))->execute([]);

        // un second passage réécrirait le pseudo en 'Csuppr' + id
        $this->em->createQuery('UPDATE ' . User::class . " u SET u.nickname = 'marqueur' WHERE u.id IN (:ids)")
            ->setParameter('ids', $this->idsDesComptesDeTest())
            ->execute();
        $this->em->clear();

        $tester = new CommandTester($this->commandeLimiteeAuxComptesDeTest());
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->em->clear();
        foreach ($this->idsDesComptesDeTest() as $id) {
            $this->assertSame('marqueur', $this->em->find(User::class, $id)->getNickname(), 'un compte déjà anonymisé ne doit pas être retraité');
        }
    }

    /**
     * La commande sélectionne tous les comptes sous la coupure : on la restreint aux comptes de test pour ne pas anonymiser les fixtures.
     */
    private function commandeLimiteeAuxComptesDeTest(): UserAnonymizationCronCommand
    {
        $container = self::getContainer();
        $ids = $this->idsDesComptesDeTest();

        $repository = new class($container->get('doctrine'), $ids) extends UserRepository {
            /** @param int[] $ids */
            public function __construct(ManagerRegistry $registry, private readonly array $ids)
            {
                parent::__construct($registry);
            }

            public function findUsersWithoutActivity(?\DateTime $end = null)
            {
                return array_values(array_filter(parent::findUsersWithoutActivity($end), fn (User $u) => \in_array($u->getId(), $this->ids, true)));
            }

            public function findUsersWithActivity(?\DateTime $end = null)
            {
                return array_values(array_filter(parent::findUsersWithActivity($end), fn (User $u) => \in_array($u->getId(), $this->ids, true)));
            }
        };

        return new UserAnonymizationCronCommand(
            $repository,
            $container->get(FormationValidationBrevetRepository::class),
            $container->get(UserNotificationRepository::class),
            $container->get(UserAttrRepository::class),
            $container->get(UserLicenseHelper::class),
            new NullLogger(),
            $this->em,
        );
    }

    /** @return int[] */
    private function idsDesComptesDeTest(): array
    {
        return array_map('intval', $this->em->createQuery('SELECT u.id FROM ' . User::class . ' u WHERE u.cafnum IN (:cafnums)')
            ->setParameter('cafnums', [self::CAFNUM_PHOTO_1, self::CAFNUM_PHOTO_2, self::CAFNUM_SANS_PHOTO])
            ->getSingleColumnResult());
    }

    private function persistUser(string $cafnum): User
    {
        $user = (new User())
            ->setCafnum($cafnum)
            ->setFirstname('Test')
            ->setLastname('Commande')
            ->setNickname($cafnum)
            ->setEmail(strtolower($cafnum) . '@test-anonymisation.example')
            ->setProfileType(User::PROFILE_CLUB_MEMBER)
            ->setJoinDate(new \DateTimeImmutable('2022-09-15 00:00:00'))
        ;

        $this->em->persist($user);

        return $user;
    }

    private function supprimerLesComptesDeTest(): void
    {
        $ids = $this->idsDesComptesDeTest();
        if ([] === $ids) {
            return;
        }

        $this->em->createQuery('UPDATE ' . User::class . ' u SET u.profilePicture = NULL WHERE u.id IN (:ids)')->setParameter('ids', $ids)->execute();
        $this->em->createQuery('DELETE FROM ' . MediaUpload::class . ' m WHERE m.uploadedBy IN (:ids)')->setParameter('ids', $ids)->execute();
        $this->em->createQuery('DELETE FROM ' . User::class . ' u WHERE u.id IN (:ids)')->setParameter('ids', $ids)->execute();
    }
}
