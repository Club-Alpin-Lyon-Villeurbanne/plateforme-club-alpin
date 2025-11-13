<?php

namespace App\Tests\Service;

use App\Repository\UserRepository;
use App\Service\FfcamSynchronizer;
use App\Tests\TestHelpers\FfcamTestHelper;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use SlopeIt\ClockMock\ClockMock;

class FfcamSynchronizerTest extends WebTestCase
{
    private $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create('fr_FR');
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
}
