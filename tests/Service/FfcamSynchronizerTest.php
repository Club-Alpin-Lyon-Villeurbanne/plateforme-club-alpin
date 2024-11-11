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
    protected function tearDown(): void
    {
        $cafnums = ['690099990001', '690099990002'];
        foreach ($cafnums as $cafnum) {
            $user = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber($cafnum);
            if ($user) {
                self::getContainer()->get(EntityManagerInterface::class)->remove($user);
            }
        }
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        parent::tearDown();
    }

    public function testSynchronizeCreatesNewUsers(): void
    {
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => '690099990001',
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
            ],
            [
                'cafnum' => '690099990002',
                'lastname' => 'MARTIN',
                'firstname' => 'PIERRE',
            ],
        ]);

        $synchronizer = self::getContainer()->get(FfcamSynchronizer::class);

        $this->assertTrue(null === self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber('690099990001'));
        $this->assertTrue(null === self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber('690099990002'));

        $synchronizer->synchronize($filePath);

        $user1 = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber('690099990001');
        $this->assertNotNull($user1->getId());
        $this->assertEquals('Jean', $user1->getFirstname());
        $this->assertEquals('Dupont', $user1->getLastname());
        $this->assertEquals('0687000001', $user1->getTel());
        $this->assertEquals('Lyon', $user1->getVille());

        $user2 = self::getContainer()->get(UserRepository::class)->findOneByLicenseNumber('690099990002');
        $this->assertNotNull($user2->getId());
        $this->assertEquals('Pierre', $user2->getFirstname());
    }

    public function testSynchronizeUpdatesExistingUsers(): void
    {
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => '690099990001',
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
            ],
        ]);

        $existingUser = $this->signup();
        $existingUser
            ->setCafnum('690099990001')
            ->setFirstname('Jeanne')
            ->setEmail('custom@email.com')
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
        $this->assertEquals('custom@email.com', $existingUser->getEmail());
        $this->assertEquals('hashedpassword', $existingUser->getPassword());
    }

    public function testSynchronizeSetsLicenceExpiredFlagOnly(): void
    {
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => '690099990001',
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
                'adhesionDate' => '0000-00-00',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-10-15'));

        $existingUser = $this->signup();
        $existingUser
            ->setCafnum('690099990001')
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
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => '690099990001',
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
                'adhesionDate' => '0000-00-00',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-11-15'));

        $existingUser = $this->signup();
        $existingUser
            ->setCafnum('690099990001')
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
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => '690099990001',
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
                'adhesionDate' => '0000-00-00',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-11-01'));

        $expiredUser = $this->signup();
        $expiredUser
            ->setCafnum('690099990001')
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
        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => '690099990001',
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
                'adhesionDate' => '2024-11-15',
            ],
        ]);

        ClockMock::freeze(new \DateTime('2024-11-16'));

        $expiredUser = $this->signup();
        $expiredUser
            ->setCafnum('690099990001')
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
