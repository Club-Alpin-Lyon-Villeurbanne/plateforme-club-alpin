<?php

namespace App\Tests\Service;

use App\Repository\UserRepository;
use App\Service\FfcamSynchronizer;
use App\Tests\TestHelpers\FfcamTestHelper;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use SlopeIt\ClockMock\ClockMock;

class FfcamSynchronizerTest extends WebTestCase
{
    public function testSynchronizeCreatesNewUsers(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $identifiant2 = rand(100000000000, 999999999999);

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
            ],
            [
                'cafnum' => $identifiant2,
                'lastname' => 'MARTIN',
                'firstname' => 'PIERRE',
            ],
        ]);

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);

        $this->assertTrue(null === self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant1));
        $this->assertTrue(null === self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant2));

        $synchronizer->synchronize($filePath);

        $user1 = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant1);
        $this->assertNotNull($user1->getId());
        $this->assertEquals('Jean', $user1->getFirstname());
        $this->assertEquals('Dupont', $user1->getLastname());
        $this->assertEquals('0687000001', $user1->getTel());
        $this->assertEquals('Lyon', $user1->getVille());

        $user2 = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($identifiant2);
        $this->assertNotNull($user2->getId());
        $this->assertEquals('Pierre', $user2->getFirstname());
    }

    public function testSynchronizeUpdatesExistingUsers(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
            ],
        ]);

        $email = 'test-' . bin2hex(random_bytes(10)) . '@clubalpinlyon.fr';
        $existingUser = $this->signup();
        $existingUser
            ->setCafnum($identifiant1)
            ->setFirstname('Jeanne')
            ->setEmail($email)
            ->setPassword('hashedpassword');

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($existingUser);
        $em->flush();

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        $em->refresh($existingUser);

        $this->assertEquals('Jean', $existingUser->getFirstname());
        $this->assertEquals('Dupont', $existingUser->getLastname());
        $this->assertEquals('0687000001', $existingUser->getTel());
        $this->assertEquals($email, $existingUser->getEmail());
        $this->assertEquals('hashedpassword', $existingUser->getPassword());
    }

    public function testSynchronizeSetsLicenceExpiredFlagOnly(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
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

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->refresh($existingUser);

        $this->assertTrue($existingUser->getAlerteRenouveler());
        $this->assertFalse($existingUser->getDoitRenouveler());

        ClockMock::reset();
    }

    public function testSynchronizeSetsExpiredAndRenewalFlag(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
                'adhesionDate' => '0000-00-00',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-11-15'));

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

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->refresh($existingUser);

        $this->assertTrue($existingUser->getAlerteRenouveler());
        $this->assertTrue($existingUser->getDoitRenouveler());

        ClockMock::reset();
    }

    public function testSynchronizeBlocksExpiredAccounts(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
                'adhesionDate' => '0000-00-00',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-11-01'));

        $expiredUser = $this->signup();
        $expiredUser
            ->setCafnum($identifiant1)
            ->setDoitRenouveler(false);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($expiredUser);
        $em->flush();

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);
        $synchronizer->synchronize($filePath);

        $em->refresh($expiredUser);

        $this->assertTrue($expiredUser->getDoitRenouveler());
    }

    public function testSynchronizeUnblocksExpiredAccounts(): void
    {
        $identifiant1 = rand(100000000000, 999999999999);
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => $identifiant1,
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
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

        $em->refresh($expiredUser);

        $this->assertFalse($expiredUser->getDoitRenouveler());
        $this->assertFalse($expiredUser->getDoitRenouveler());

        ClockMock::reset();
    }
}
