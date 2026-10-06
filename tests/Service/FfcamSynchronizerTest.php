<?php

namespace App\Tests\Service;

use App\Repository\UserRepository;
use App\Service\FfcamFileParser;
use App\Service\FfcamSynchronizer;
use App\Service\FfcamSyncReportMailer;
use App\Service\UserLicenseHelper;
use App\Tests\TestHelpers\FfcamTestHelper;
use App\Tests\WebTestCase;
use App\Utils\MemberMerger;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use SlopeIt\ClockMock\ClockMock;

class FfcamSynchronizerTest extends WebTestCase
{
    private $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create('fr_FR');
    }

    protected function tearDown(): void
    {
        ClockMock::reset();
        parent::tearDown();
    }

    public function testSynchronizeCreatesNewUsers(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $identifiant2 = rand(100000000000, 999999999999);

        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();
        $email1 = $this->faker->email();
        $lastname2 = $this->faker->lastName();
        $firstname2 = $this->faker->firstName();
        $email2 = $this->faker->email();

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'email' => $email1,
            ],
            [
                'cafnum' => $identifiant2,
                'lastname' => $lastname2,
                'firstname' => $firstname2,
                'email' => $email2,
            ],
        ]);

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);

        $this->assertTrue(null === self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant1));
        $this->assertTrue(null === self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant2));

        $synchronizer->synchronize($filePath);

        $user1 = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant1);
        $this->assertNotNull($user1->getId());
        $this->assertEquals(mb_strtolower($firstname1), mb_strtolower($user1->getFirstname()));
        $this->assertEquals(mb_strtolower($lastname1), mb_strtolower($user1->getLastname()));
        $this->assertEquals('0687000001', $user1->getTel());
        $this->assertEquals('Lyon', $user1->getVille());

        $user2 = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant2);
        $this->assertNotNull($user2->getId());
        $this->assertEquals(mb_strtolower($firstname2), mb_strtolower($user2->getFirstname()));
        $this->assertEquals(mb_strtolower($lastname2), mb_strtolower($user2->getLastname()));
    }

    public function testSynchronizeUpdatesExistingUsers(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);

        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();
        $email = 'test-' . bin2hex(random_bytes(10)) . '@clubalpinlyon.fr';

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'email' => $email,
            ],
        ]);

        $existingUser = $this->signup();
        $existingUser
            ->setCafnum($identifiant1)
            ->setFirstname($firstname1)
            ->setEmail($email)
            ->setPassword('hashedpassword');

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($existingUser);
        $em->flush();

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload the entity from the database after synchronize() clears the EntityManager
        $existingUser = self::getContainer()->get(UserRepository::class)->find($existingUser->getId());

        $this->assertEquals(mb_strtolower($firstname1), mb_strtolower($existingUser->getFirstname()));
        $this->assertEquals(mb_strtolower($lastname1), mb_strtolower($existingUser->getLastname()));
        $this->assertEquals('0687000001', $existingUser->getTel());
        $this->assertEquals($email, $existingUser->getEmail());
        $this->assertEquals('hashedpassword', $existingUser->getPassword());
    }

    public function testSynchronizeSetsLicenceExpiredFlagOnly(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'adhesionDate' => '0000-00-00',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-09-15'));

        $existingUser = $this->signup();
        $existingUser
            ->setCafnum($identifiant1)
            ->setDoitRenouveler(false)
            ->setAlerteRenouveler(false);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($existingUser);
        $em->flush();

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload the entity from the database after synchronize() clears the EntityManager
        $existingUser = self::getContainer()->get(UserRepository::class)->find($existingUser->getId());

        $this->assertTrue($existingUser->getAlerteRenouveler());
        $this->assertFalse($existingUser->getDoitRenouveler());

        ClockMock::reset();
    }

    public function testSynchronizeSetsExpiredAndRenewalFlag(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'adhesionDate' => '0000-00-00',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-10-15'));

        $existingUser = $this->signup();
        $existingUser
            ->setCafnum($identifiant1)
            ->setDoitRenouveler(false)
            ->setAlerteRenouveler(false);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($existingUser);
        $em->flush();

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload the entity from the database after synchronize() clears the EntityManager
        $existingUser = self::getContainer()->get(UserRepository::class)->find($existingUser->getId());

        $this->assertFalse($existingUser->getAlerteRenouveler());
        $this->assertTrue($existingUser->getDoitRenouveler());

        ClockMock::reset();
    }

    public function testSynchronizeBlocksExpiredAccounts(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'adhesionDate' => '0000-00-00',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-10-01'));

        $expiredUser = $this->signup();
        $expiredUser
            ->setCafnum($identifiant1)
            ->setDoitRenouveler(false);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($expiredUser);
        $em->flush();

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload the entity from the database after synchronize() clears the EntityManager
        $expiredUser = self::getContainer()->get(UserRepository::class)->find($expiredUser->getId());

        $this->assertTrue($expiredUser->getDoitRenouveler());
    }

    public function testSynchronizeUnblocksExpiredAccounts(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'adhesionDate' => '2024-11-15',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-11-16'));

        $expiredUser = $this->signup();
        $expiredUser
            ->setCafnum($identifiant1)
            ->setDoitRenouveler(true)
            ->setAlerteRenouveler(true);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($expiredUser);
        $em->flush();

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload the entity from the database after synchronize() clears the EntityManager
        $expiredUser = self::getContainer()->get(UserRepository::class)->find($expiredUser->getId());

        $this->assertFalse($expiredUser->getDoitRenouveler());
        $this->assertFalse($expiredUser->getAlerteRenouveler());

        ClockMock::reset();
    }

    public function testSynchronizeDetectsAndMergesDuplicateUsers(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $identifiant2 = rand(100000000000, 999999999999);

        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();
        $email1 = $this->faker->email();

        $existingUser = $this->signup();
        $existingUser
            ->setCafnum($identifiant1)
            ->setFirstname($firstname1)
            ->setLastname($lastname1)
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setDoitRenouveler(true)
            ->setEmail($email1)
            ->setPassword('hashedpassword');

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($existingUser);
        $em->flush();

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant2,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'email' => $email1,
                'birthday' => '1990-01-01', // 1990-01-01
            ],
        ]);

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload the entity from the database after synchronize() clears the EntityManager
        $existingUser = self::getContainer()->get(UserRepository::class)->find($existingUser->getId());
        $this->assertEquals($identifiant2, $existingUser->getCafnum());
        $this->assertEquals($email1, $existingUser->getEmail());
        $this->assertEquals('hashedpassword', $existingUser->getPassword());
        $this->assertSame('Masculin', $existingUser->getCiv());

        $duplicateUser = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant1);
        $this->assertNull($duplicateUser);
    }

    public function testSynchronizeMergesEvenIfNonExpiredUsers(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $identifiant2 = rand(100000000000, 999999999999);

        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();
        $email = 'test-' . bin2hex(random_bytes(10)) . '@clubalpinlyon.fr';

        $existingUser = $this->signup();
        $existingUser
            ->setCafnum($identifiant1)
            ->setFirstname($firstname1)
            ->setLastname($lastname1)
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setEmail($email)
            ->setDoitRenouveler(false)
            ->setAlerteRenouveler(false);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($existingUser);
        $em->flush();

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant2,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'birthday' => '1990-01-01', // 1990-01-01
                'email' => $email,
            ],
        ]);

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload the entity from the database after synchronize() clears the EntityManager
        $existingUser = self::getContainer()->get(UserRepository::class)->find($existingUser->getId());

        // Le compte existant doit être fusionné avec le nouveau numéro même si "doit_renouveler" est à false
        $this->assertEquals($identifiant2, $existingUser->getCafnum());
        // Et aucun doublon ne doit être créé avec l'ancien numéro
        $duplicate = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant1);
        $this->assertNull($duplicate);
    }

    public function testSynchronizeSelectsMostRecentDuplicate(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $identifiant2 = rand(100000000000, 999999999999);
        $identifiant3 = rand(100000000000, 999999999999);

        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();
        // Use a common email that will be matched by the synced member
        $email = 'test-' . bin2hex(random_bytes(10)) . '@clubalpinlyon.fr';

        $now = new \DateTime();
        $minusOneHour = (clone $now)->modify('-1 hour');

        $existingUser1 = $this->signup();
        $existingUser1
            ->setCafnum($identifiant1)
            ->setFirstname($firstname1)
            ->setLastname($lastname1)
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            // User1 has no email (NULL) - won't match duplicate detection
            ->setCreatedAt($minusOneHour)
            ->setUpdatedAt($minusOneHour)
            ->setDoitRenouveler(true);

        $existingUser2 = $this->signup();
        $existingUser2
            ->setCafnum($identifiant2)
            ->setFirstname($firstname1)
            ->setLastname($lastname1)
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setEmail($email)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setDoitRenouveler(true);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($existingUser1);
        $em->persist($existingUser2);
        $em->flush();

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant3,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'birthday' => '1990-01-01',
                'email' => $email,
            ],
        ]);

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload entities from the database after synchronize() clears the EntityManager
        $existingUser2 = self::getContainer()->get(UserRepository::class)->find($existingUser2->getId());
        $existingUser1 = self::getContainer()->get(UserRepository::class)->find($existingUser1->getId());

        // Vérifie que c'est bien l'utilisateur le plus récent qui a été mis à jour
        $this->assertEquals($identifiant3, $existingUser2->getCafnum());

        // Vérifie que l'ancien utilisateur n'a pas été modifié
        $this->assertEquals($identifiant1, $existingUser1->getCafnum());
    }

    public function testHandlesUpdatesUserEvenIfMergeIsPossible(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $identifiant2 = rand(100000000000, 999999999999);

        $lastname1 = $this->faker->lastName();
        $firstname1 = $this->faker->firstName();

        $user1 = $this->signup();
        $user1
            ->setCafnum($identifiant1)
            ->setFirstname($firstname1)
            ->setLastname($lastname1)
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setTel('0606060606');

        $user2 = $this->signup();
        $user2
            ->setCafnum($identifiant2)
            ->setFirstname($firstname1)
            ->setLastname($lastname1)
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setTel('0606060606');

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user1);
        $em->persist($user2);
        $em->flush();

        // Premier merge : les deux utilisateurs vers identifiant3
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => $lastname1,
                'firstname' => $firstname1,
                'birthday' => '1990-01-01',
                'tel' => '0606060676',
            ],
        ]);

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        // Reload entities from the database after synchronize() clears the EntityManager
        $user1 = self::getContainer()->get(UserRepository::class)->find($user1->getId());
        $user2 = self::getContainer()->get(UserRepository::class)->find($user2->getId());

        $this->assertEquals($identifiant1, $user1->getCafnum());
        $this->assertEquals($user1->getTel(), '0606060676');

        // Check that user2 has not been merged (ie cafnum is not changed)
        $this->assertEquals($identifiant2, $user2->getCafnum());
    }

    public function testSynchronizeConsolidatesUnmergedDuplicate(): void
    {
        // Incident juin 2026 : une personne avec DEUX fiches vivantes — l'historique
        // (compte + email propre) et une fiche au cafnum du fichier (sans compte, email
        // masqué). La fusion réécrivait un cafnum déjà pris -> violation -> EM fermé ->
        // cascade. Désormais on consolide : la fiche en double est parquée et l'historique
        // récupère le cafnum du fichier en conservant son compte.
        [$row, $oldCafnum, $newCafnum, $historicId] = $this->createDuplicatePair(null);

        $nextCafnum = (string) rand(100000000000, 999999999999);
        $filePath = FfcamTestHelper::generateFile([
            $row,
            [
                'cafnum' => $nextCafnum,
                'lastname' => $this->faker->lastName(),
                'firstname' => $this->faker->firstName(),
                'email' => 'suivant-' . bin2hex(random_bytes(8)) . '@clubalpinlyon.fr',
            ],
        ]);

        self::getContainer()->get(FfcamSynchronizer::class)->synchronize($filePath);

        $repository = self::getContainer()->get(UserRepository::class);

        // Le cafnum du fichier pointe désormais sur la fiche HISTORIQUE, compte conservé.
        $survivor = $repository->findOneByLicenseNumber($newCafnum);
        $this->assertNotNull($survivor);
        $this->assertSame($historicId, $survivor->getId(), 'La fiche historique doit survivre et porter le cafnum du fichier');
        $this->assertSame('hashedpassword', $survivor->getPassword(), 'Le compte (mot de passe) doit être conservé');

        // L'ancien cafnum n'existe plus, et l'adhérent suivant a bien été traité (pas de cascade).
        $this->assertNull($repository->findOneByLicenseNumber($oldCafnum));
        $this->assertNotNull($repository->findOneByLicenseNumber($nextCafnum));
    }

    public function testSynchronizeDoesNotAutoMergeWhenDuplicateHasAccount(): void
    {
        // Garde-fou : si la fiche au cafnum du fichier porte AUSSI un compte, on ne détruit
        // rien automatiquement (ambigu) — on alerte et on saute, sans casser la suite.
        [$row, $oldCafnum, $newCafnum] = $this->createDuplicatePair('hashedpassword-dup');

        $nextCafnum = (string) rand(100000000000, 999999999999);
        $filePath = FfcamTestHelper::generateFile([
            $row,
            [
                'cafnum' => $nextCafnum,
                'lastname' => $this->faker->lastName(),
                'firstname' => $this->faker->firstName(),
                'email' => 'suivant-' . bin2hex(random_bytes(8)) . '@clubalpinlyon.fr',
            ],
        ]);

        self::getContainer()->get(FfcamSynchronizer::class)->synchronize($filePath);

        $repository = self::getContainer()->get(UserRepository::class);

        // Aucune fusion automatique : les deux fiches subsistent à leur cafnum d'origine.
        $this->assertNotNull($repository->findOneByLicenseNumber($oldCafnum));
        $this->assertNotNull($repository->findOneByLicenseNumber($newCafnum));
        // La suite du fichier est quand même traitée (skip, pas de cascade).
        $this->assertNotNull($repository->findOneByLicenseNumber($nextCafnum));
    }

    public function testSynchronizeConsolidationKeepsDuplicateParticipations(): void
    {
        // La consolidation parque la fiche en double : ses inscriptions aux sorties
        // doivent suivre la fiche gardée, sinon elles deviendraient invisibles.
        [$row, , $newCafnum, $historicId] = $this->createDuplicatePair(null);

        // La fiche en double a une inscription à une sortie.
        $duplicate = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($newCafnum);
        $duplicateId = $duplicate->getId();
        $this->createEvent($duplicate);

        $filePath = FfcamTestHelper::generateFile([$row]);
        self::getContainer()->get(FfcamSynchronizer::class)->synchronize($filePath);

        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $onHistoric = (int) $connection->fetchOne('SELECT COUNT(*) FROM caf_evt_join WHERE user_evt_join = ?', [$historicId]);
        $onDuplicate = (int) $connection->fetchOne('SELECT COUNT(*) FROM caf_evt_join WHERE user_evt_join = ?', [$duplicateId]);

        $this->assertSame(1, $onHistoric, "L'inscription doit être rattachée à la fiche gardée");
        $this->assertSame(0, $onDuplicate, "La fiche parquée ne doit plus porter d'inscription");
    }

    public function testSynchronizeConsolidationDeduplicatesParticipations(): void
    {
        // Si les deux fiches sont déjà inscrites à la MÊME sortie, la consolidation ne doit
        // pas créer de double inscription sur la fiche gardée.
        [$row, , $newCafnum, $historicId] = $this->createDuplicatePair(null);

        $repository = self::getContainer()->get(UserRepository::class);
        $historic = $repository->find($historicId);
        $duplicate = $repository->findOneByLicenseNumber($newCafnum);
        $duplicateId = $duplicate->getId();

        // Une sortie où les DEUX fiches sont inscrites.
        $event = $this->createEvent($historic);
        $event->addParticipation($duplicate);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($event);
        $em->flush();
        $eventId = $event->getId();

        $filePath = FfcamTestHelper::generateFile([$row]);
        self::getContainer()->get(FfcamSynchronizer::class)->synchronize($filePath);

        $connection = $em->getConnection();
        $onEventForHistoric = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM caf_evt_join WHERE user_evt_join = ? AND evt_evt_join = ?',
            [$historicId, $eventId]
        );
        $onDuplicate = (int) $connection->fetchOne('SELECT COUNT(*) FROM caf_evt_join WHERE user_evt_join = ?', [$duplicateId]);

        $this->assertSame(1, $onEventForHistoric, 'Pas de double inscription sur la fiche gardée');
        $this->assertSame(0, $onDuplicate, "La fiche parquée ne porte plus d'inscription");
    }

    /**
     * Crée une personne avec deux fiches vivantes : l'historique (compte + email propre)
     * et une fiche au cafnum du fichier (email masqué). $duplicatePassword contrôle si la
     * fiche en double porte un compte (pour tester le garde-fou d'ambiguïté).
     *
     * @return array{0: array<string, string>, 1: string, 2: string, 3: int} [ligne fichier, ancien cafnum, nouveau cafnum, id historique]
     */
    private function createDuplicatePair(?string $duplicatePassword): array
    {
        $oldCafnum = (string) rand(100000000000, 999999999999);
        $newCafnum = (string) rand(100000000000, 999999999999);
        $lastname = $this->faker->lastName();
        $firstname = $this->faker->firstName();
        $email = 'poison-' . bin2hex(random_bytes(8)) . '@clubalpinlyon.fr';

        // Fiche historique : possède un compte (mot de passe) et l'email propre.
        $historic = $this->signup(null, 'hashedpassword');
        $historic
            ->setCafnum($oldCafnum)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setEmail($email)
            ->setDoitRenouveler(true);

        // Fiche en double au cafnum du fichier : email masqué, compte selon le paramètre.
        $duplicate = $this->signup(null, $duplicatePassword);
        $duplicate
            ->setCafnum($newCafnum)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setEmail('doublon.' . $newCafnum . '-' . $email);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($historic);
        $em->persist($duplicate);
        $em->flush();

        $row = [
            'cafnum' => $newCafnum,
            'lastname' => $lastname,
            'firstname' => $firstname,
            'birthday' => '1990-01-01',
            'email' => $email,
        ];

        return [$row, $oldCafnum, $newCafnum, $historic->getId()];
    }

    // Régression prod 02/09/2026 : une ville de 33 caractères dépassait ville_user(30),
    // le flush fermait l'EntityManager et les 34 adhérents suivants du fichier étaient perdus.
    public function testSynchronizeImportsMembersFollowingAnOverlongCity(): void
    {
        $identifiantVilleLongue = rand(100000000000, 999999999999);
        $identifiantSuivant = rand(100000000000, 999999999999);

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiantVilleLongue,
                'lastname' => $this->faker->lastName(),
                'firstname' => $this->faker->firstName(),
                'email' => $this->faker->email(),
                'ville' => 'VILLENEUVE-LA-GARENNE, 92, FRANCE',
            ],
            [
                'cafnum' => $identifiantSuivant,
                'lastname' => $this->faker->lastName(),
                'firstname' => $this->faker->firstName(),
                'email' => $this->faker->email(),
            ],
        ]);

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        $userRepository = self::getContainer()->get(UserRepository::class);

        $userVilleLongue = $userRepository->findOneByLicenseNumber($identifiantVilleLongue);
        $this->assertNotNull($userVilleLongue, "L'adhérent dont la ville dépasse 30 caractères doit être importé");
        $this->assertEquals('Villeneuve-La-Garenne, 92, France', $userVilleLongue->getVille());

        $this->assertNotNull(
            $userRepository->findOneByLicenseNumber($identifiantSuivant),
            'Les adhérents suivants ne doivent pas être perdus'
        );
    }

    public function testSynchronizeNormalizesOldAndNewSexeFormats(): void
    {
        $attendus = [
            'M' => 'Masculin',
            'M.' => 'Masculin',
            'MME' => 'Féminin',
            'Mme' => 'Féminin',
            'MLLE' => 'Féminin',
            'Masculin' => 'Masculin',
            'Féminin' => 'Féminin',
            ' féminin ' => 'Féminin',
            'Autre' => 'Autre',
            '' => null,
        ];

        $membres = [];
        foreach (array_keys($attendus) as $sexe) {
            $membres[(string) $sexe] = $this->membre(['sexe' => (string) $sexe]);
        }

        self::getContainer()->get(FfcamSynchronizer::class)->synchronize(FfcamTestHelper::generateFile(array_values($membres)));

        $userRepository = self::getContainer()->get(UserRepository::class);
        foreach ($attendus as $sexe => $attendu) {
            $user = $userRepository->findOneByLicenseNumber($membres[(string) $sexe]['cafnum']);
            $this->assertSame($attendu, $user->getCiv(), "Sexe « $sexe » dans le fichier");
        }
    }

    public function testSynchronizeKeepsUnknownSexeAndAlertsOnce(): void
    {
        $inconnu1 = $this->membre(['sexe' => 'Non renseigné']);
        $inconnu2 = $this->membre(['sexe' => 'Non renseigné']);
        $suivant = $this->membre();
        // Une adresse partagée produit un avertissement par adhérent : le mail n'en affiche que 10.
        $famille = array_map(fn () => $this->membre(['email' => 'famille@example.org']), range(1, 11));

        $journal = new TestHandler();
        $rapports = [];
        $this->synchroniseurObserve($journal, $rapports)->synchronize(FfcamTestHelper::generateFile([...$famille, $inconnu1, $inconnu2, $suivant]));

        $userRepository = self::getContainer()->get(UserRepository::class);
        $this->assertSame('Non rensei', $userRepository->findOneByLicenseNumber($inconnu1['cafnum'])->getCiv());
        $this->assertSame('Non rensei', $userRepository->findOneByLicenseNumber($inconnu2['cafnum'])->getCiv());
        $this->assertNotNull($userRepository->findOneByLicenseNumber($suivant['cafnum']));

        $erreurs = $this->erreurs($journal);
        $this->assertCount(1, $erreurs);
        $this->assertSame('Valeurs de sexe inconnues dans le fichier FFCAM', $erreurs[0]->message, 'Message fixe : Sentry regroupe les passages');
        $this->assertStringContainsString('Non renseigné (2)', $erreurs[0]->context['valeurs']);

        $this->assertCount(1, $rapports);
        $this->assertGreaterThan(10, \count($rapports[0]['warning_details']));
        $this->assertStringContainsString('Non renseigné (2)', implode("\n", \array_slice($rapports[0]['warning_details'], 0, 10)));
    }

    /**
     * @dataProvider fichiersAnnuelsIncomplets
     */
    public function testSynchronizeAbortsWithoutBlockingAnyoneWhenColumnsAreMissing(string $cas): void
    {
        ClockMock::freeze(new \DateTime('2024-10-01'));

        $adherent = $this->signup();
        $adherent
            ->setCafnum((string) rand(100000000000, 999999999999))
            ->setJoinDate(new \DateTimeImmutable('2023-09-01'))
            ->setNomade(false)
            ->setDoitRenouveler(false);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($adherent);
        $em->flush();

        $nouveau = $this->membre(['adhesionDate' => '2024-09-15']);
        $fichier = FfcamTestHelper::generateFile([$this->membre(), $nouveau]);
        if ('première ligne tronquée' === $cas) {
            $lignes = explode("\n", file_get_contents($fichier));
            $lignes[0] = implode(';', \array_slice(explode(';', $lignes[0]), 0, 20));
            file_put_contents($fichier, implode("\n", $lignes));
        } else {
            file_put_contents($fichier, '');
        }

        $journal = new TestHandler();
        $rapports = [];
        $this->synchroniseurObserve($journal, $rapports)->synchronize($fichier);
        $em->clear();

        $userRepository = self::getContainer()->get(UserRepository::class);
        $this->assertFalse($userRepository->find($adherent->getId())->getDoitRenouveler(), 'Personne ne doit être bloqué');
        $this->assertNull($userRepository->findOneByLicenseNumber($nouveau['cafnum']), 'Aucune ligne ne doit être traitée');
        $this->assertCount(1, $this->erreurs($journal));
    }

    public static function fichiersAnnuelsIncomplets(): array
    {
        return [
            'première ligne tronquée' => ['première ligne tronquée'],
            'fichier vide' => ['fichier vide'],
        ];
    }

    /**
     * @dataProvider fichiersAuNombreDeColonnesInattendu
     */
    public function testSynchronizeWarnsButImportsWhenColumnCountChanges(int $ecart): void
    {
        $membre = $this->membre();
        $fichier = FfcamTestHelper::generateFile([$membre]);
        $colonnes = explode(';', rtrim(file_get_contents($fichier), "\n"));
        $colonnes = $ecart > 0 ? array_merge($colonnes, array_fill(0, $ecart, 'EN PLUS')) : \array_slice($colonnes, 0, $ecart);
        file_put_contents($fichier, implode(';', $colonnes) . "\n");

        $journal = new TestHandler();
        $rapports = [];
        $this->synchroniseurObserve($journal, $rapports)->synchronize($fichier);

        $this->assertNotNull(self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($membre['cafnum']));
        $this->assertCount(0, $this->erreurs($journal));
        $this->assertStringContainsString('colonnes', implode("\n", $rapports[0]['warning_details']));
    }

    public static function fichiersAuNombreDeColonnesInattendu(): array
    {
        return [
            'colonnes en plus' => [10],
            'colonnes en moins, au-dessus du minimum' => [-2],
        ];
    }

    public function testSynchronizeIgnoresLeadingBlankLines(): void
    {
        $membre = $this->membre();
        $fichier = FfcamTestHelper::generateFile([$membre]);
        file_put_contents($fichier, "\n" . file_get_contents($fichier));

        $journal = new TestHandler();
        $rapports = [];
        $this->synchroniseurObserve($journal, $rapports)->synchronize($fichier);

        $this->assertNotNull(self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($membre['cafnum']));
        $this->assertCount(0, $this->erreurs($journal));
    }

    public function testDiscoverySynchronizeNormalizesSexeAndAlertsOnUnknownValues(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'ffcam_');
        file_put_contents($fichier, $this->ligneDecouverte('D100001', 'MLLE') . $this->ligneDecouverte('D100002', 'Inconnu'));

        $journal = new TestHandler();
        $rapports = [];
        $this->synchroniseurObserve($journal, $rapports)->discoverySynchronize($fichier);

        $userRepository = self::getContainer()->get(UserRepository::class);
        $this->assertSame('Féminin', $userRepository->findOneByLicenseNumber('D100001')->getCiv());
        $this->assertSame('Inconnu', $userRepository->findOneByLicenseNumber('D100002')->getCiv());

        $erreurs = $this->erreurs($journal);
        $this->assertCount(1, $erreurs);
        $this->assertStringContainsString('Inconnu (1)', $erreurs[0]->context['valeurs']);
    }

    public function testDiscoverySynchronizeAbortsWhenColumnsAreMissing(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'ffcam_');
        file_put_contents($fichier, $this->ligneDecouverte('D100003', 'M', 10) . $this->ligneDecouverte('D100004'));

        $journal = new TestHandler();
        $rapports = [];
        $this->synchroniseurObserve($journal, $rapports)->discoverySynchronize($fichier);

        $this->assertNull(self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber('D100004'), 'Aucune ligne ne doit être traitée');
        $this->assertCount(1, $this->erreurs($journal));
    }

    public function testDiscoverySynchronizeAcceptsEmptyFile(): void
    {
        $fichier = tempnam(sys_get_temp_dir(), 'ffcam_');
        file_put_contents($fichier, "\n");

        $journal = new TestHandler();
        $rapports = [];
        $this->synchroniseurObserve($journal, $rapports)->discoverySynchronize($fichier);

        $this->assertCount(0, $this->erreurs($journal));
        $this->assertCount(1, $rapports);
    }

    private function membre(array $valeurs = []): array
    {
        return $valeurs + [
            'cafnum' => rand(100000000000, 999999999999),
            'lastname' => $this->faker->lastName(),
            'firstname' => $this->faker->firstName(),
            'email' => $this->faker->unique()->email(),
        ];
    }

    private function ligneDecouverte(string $numero, string $sexe = 'M', int $colonnes = 25): string
    {
        $champs = array_fill(0, $colonnes, '');
        $valeurs = [$numero, '24', '2099-01-01', '08:00', '1990-05-20', $sexe, $this->faker->lastName(), $this->faker->firstName()];
        array_splice($champs, 0, \count($valeurs), $valeurs);
        if ($colonnes > 16) {
            $champs[16] = $this->faker->unique()->email();
        }

        return mb_convert_encoding(implode(';', $champs), 'ISO-8859-1', 'UTF-8') . "\n";
    }

    private function synchroniseurObserve(TestHandler $journal, array &$rapports): FfcamSynchronizer
    {
        $rapport = $this->createMock(FfcamSyncReportMailer::class);
        $rapport->method('sendSyncReport')->willReturnCallback(function (array $stats) use (&$rapports) {
            $rapports[] = $stats;
        });

        $container = self::getContainer();

        return new FfcamSynchronizer(
            new Logger('test', [$journal]),
            $container->get(EntityManagerInterface::class),
            $container->get(UserRepository::class),
            $container->get(FfcamFileParser::class),
            $container->get(MemberMerger::class),
            $container->get(UserLicenseHelper::class),
            $rapport,
        );
    }

    /** @return LogRecord[] */
    private function erreurs(TestHandler $journal): array
    {
        return array_values(array_filter(
            $journal->getRecords(),
            static fn (LogRecord $record) => $record->level->isHigherThan(Level::Warning),
        ));
    }
}
