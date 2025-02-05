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
}
