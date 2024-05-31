<?php

namespace App\Tests\Utils;

use App\Entity\User;
use App\Utils\MemberMerger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MemberMergerTest extends KernelTestCase
{
    private $entityManager;
    private $memberMerger;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->memberMerger = static::getContainer()->get(MemberMerger::class);
    }

    public function testMergeMembers(): void
    {
        $oldLicense = '749999999985';
        $newLicense = '749999999986';


        $user1 = $this->entityManager->getRepository(User::class)->findOneByLicenseNumber($oldLicense);
        $user2 = $this->entityManager->getRepository(User::class)->findOneByLicenseNumber($newLicense);

        $this->memberMerger->mergeMembers($oldLicense, $newLicense);

        $userOldLicense = $this->entityManager->getRepository(User::class)->findOneByLicenseNumber('ligne_obsolete');
        $userNewLicense = $this->entityManager->getRepository(User::class)->findOneByLicenseNumber($newLicense);

        $this->assertSame($userNewLicense->getId(), $user1->getId());
        $this->assertSame($userOldLicense->getId(), $user2->getId());
    }
}
