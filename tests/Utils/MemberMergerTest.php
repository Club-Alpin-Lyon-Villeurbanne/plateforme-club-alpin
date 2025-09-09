<?php

namespace App\Tests\Utils;

use App\Entity\User;
use App\Tests\WebTestCase;
use App\Utils\MemberMerger;

class MemberMergerTest extends WebTestCase
{
    public function testMergeExistingMembers(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $memberMerger = static::getContainer()->get(MemberMerger::class);

        $user1 = $this->signup();
        $user2 = $this->signup();

        $oldLicense = $user1->getCafnum();
        $newLicense = $user2->getCafnum();

        $memberMerger->mergeExistingMembers($oldLicense, $newLicense);

        $userOldLicense = $entityManager->getRepository(User::class)->findOneByLicenseNumber("obs_{$newLicense}");
        $userNewLicense = $entityManager->getRepository(User::class)->findOneByLicenseNumber($newLicense);

        $this->assertSame($userNewLicense->getId(), $user1->getId());
        $this->assertSame($userOldLicense->getId(), $user2->getId());
    }

    public function testMergeNewMember(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $memberMerger = static::getContainer()->get(MemberMerger::class);

        $user1 = $this->signup();
        $user2 = new User();

        $user2Cafnum = mt_rand(100000000000, 999999999999);
        $user2->setEmail('test-' . bin2hex(random_bytes(12)) . '@clubalpinlyon.fr')
            ->setCafnum($user2Cafnum)
            ->setFirstname('prenom')
            ->setLastname('nom')
            ->setDoitRenouveler(false)
            ->setAlerteRenouveler(false);

        $oldLicense = $user1->getCafnum();

        $memberMerger->mergeNewMember($oldLicense, $user2);

        $userOldLicense = $entityManager->getRepository(User::class)->findOneByLicenseNumber("obs_{$user2Cafnum}");
        $userNewLicense = $entityManager->getRepository(User::class)->findOneByLicenseNumber($user2Cafnum);

        $this->assertSame($userNewLicense->getId(), $user1->getId());
    }

    public function testMergeExistingMembersUpdatesDateAdhesion(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $memberMerger = static::getContainer()->get(MemberMerger::class);

        $user1 = $this->signup();
        $oldDateAdhesion = new \DateTime('2020-01-15');
        $user1->setDateAdhesion($oldDateAdhesion);
        $entityManager->flush();

        $user2 = $this->signup();
        $newDateAdhesion = new \DateTime('2024-06-20');
        $user2->setDateAdhesion($newDateAdhesion);
        $entityManager->flush();

        $oldLicense = $user1->getCafnum();
        $newLicense = $user2->getCafnum();

        $memberMerger->mergeExistingMembers($oldLicense, $newLicense);

        $mergedUser = $entityManager->getRepository(User::class)->findOneByLicenseNumber($newLicense);
        
        $this->assertNotNull($mergedUser->getDateAdhesion());
        $this->assertEquals($newDateAdhesion->format('Y-m-d'), $mergedUser->getDateAdhesion()->format('Y-m-d'));
    }

    public function testMergeExistingMembersPreservesDateAdhesionWhenNewIsNull(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $memberMerger = static::getContainer()->get(MemberMerger::class);

        $user1 = $this->signup();
        $oldDateAdhesion = new \DateTime('2020-01-15');
        $user1->setDateAdhesion($oldDateAdhesion);
        $entityManager->flush();

        $user2 = $this->signup();
        $user2->setDateAdhesion(null);
        $entityManager->flush();

        $oldLicense = $user1->getCafnum();
        $newLicense = $user2->getCafnum();

        $memberMerger->mergeExistingMembers($oldLicense, $newLicense);

        $mergedUser = $entityManager->getRepository(User::class)->findOneByLicenseNumber($newLicense);
        
        $this->assertNotNull($mergedUser->getDateAdhesion());
        $this->assertEquals($oldDateAdhesion->format('Y-m-d'), $mergedUser->getDateAdhesion()->format('Y-m-d'));
    }

    public function testMergeNewMemberUpdatesDateAdhesion(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $memberMerger = static::getContainer()->get(MemberMerger::class);

        $user1 = $this->signup();
        $oldDateAdhesion = new \DateTime('2020-01-15');
        $user1->setDateAdhesion($oldDateAdhesion);
        $entityManager->flush();

        $user2 = new User();
        $user2Cafnum = mt_rand(100000000000, 999999999999);
        $newDateAdhesion = new \DateTime('2024-06-20');
        $user2->setEmail('test-' . bin2hex(random_bytes(12)) . '@clubalpinlyon.fr')
            ->setCafnum($user2Cafnum)
            ->setFirstname('prenom')
            ->setLastname('nom')
            ->setDoitRenouveler(false)
            ->setAlerteRenouveler(false)
            ->setDateAdhesion($newDateAdhesion);

        $oldLicense = $user1->getCafnum();

        $memberMerger->mergeNewMember($oldLicense, $user2);

        $mergedUser = $entityManager->getRepository(User::class)->findOneByLicenseNumber($user2Cafnum);
        
        $this->assertNotNull($mergedUser->getDateAdhesion());
        $this->assertEquals($newDateAdhesion->format('Y-m-d'), $mergedUser->getDateAdhesion()->format('Y-m-d'));
    }

    public function testMergeNewMemberPreservesDateAdhesionWhenNewIsNull(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $memberMerger = static::getContainer()->get(MemberMerger::class);

        $user1 = $this->signup();
        $oldDateAdhesion = new \DateTime('2020-01-15');
        $user1->setDateAdhesion($oldDateAdhesion);
        $entityManager->flush();

        $user2 = new User();
        $user2Cafnum = mt_rand(100000000000, 999999999999);
        $user2->setEmail('test-' . bin2hex(random_bytes(12)) . '@clubalpinlyon.fr')
            ->setCafnum($user2Cafnum)
            ->setFirstname('prenom')
            ->setLastname('nom')
            ->setDoitRenouveler(false)
            ->setAlerteRenouveler(false)
            ->setDateAdhesion(null);

        $oldLicense = $user1->getCafnum();

        $memberMerger->mergeNewMember($oldLicense, $user2);

        $mergedUser = $entityManager->getRepository(User::class)->findOneByLicenseNumber($user2Cafnum);
        
        $this->assertNotNull($mergedUser->getDateAdhesion());
        $this->assertEquals($oldDateAdhesion->format('Y-m-d'), $mergedUser->getDateAdhesion()->format('Y-m-d'));
    }
}
