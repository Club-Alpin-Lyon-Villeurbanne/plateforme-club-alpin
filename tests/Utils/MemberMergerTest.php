<?php

namespace App\Tests\Utils;

use App\Entity\User;
use App\Tests\WebTestCase;
use App\Utils\MemberMerger;

class MemberMergerTest extends WebTestCase
{
    public function testMergeMembers(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $memberMerger = static::getContainer()->get(MemberMerger::class);

        $user1 = $this->signup();
        $user2 = $this->signup();

        $oldLicense = $user1->getCafnum();
        $newLicense = $user2->getCafnum();

        $memberMerger->mergeMembers($oldLicense, $newLicense);

        $userOldLicense = $entityManager->getRepository(User::class)->findOneByLicenseNumber("obs_{$newLicense}");
        $userNewLicense = $entityManager->getRepository(User::class)->findOneByLicenseNumber($newLicense);

        $this->assertSame($userNewLicense->getId(), $user1->getId());
        $this->assertSame($userOldLicense->getId(), $user2->getId());
    }
}
