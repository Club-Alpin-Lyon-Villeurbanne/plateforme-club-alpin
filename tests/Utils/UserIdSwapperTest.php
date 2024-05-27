<?php

namespace App\Tests\Utils;

use App\Entity\User;
use App\Utils\UserIdSwapper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserIdSwapperTest extends KernelTestCase
{
    private $entityManager;
    private $userIdSwapper;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->userIdSwapper = static::getContainer()->get(UserIdSwapper::class);
    }

    public function testSwapIds(): void
    {
        $firstname1 = 'Gaston';
        $firstname2 = 'Betty';

        $userId1 = $this->createEntity($firstname1, 'Lagaffe', 10000);
        $userId2 = $this->createEntity($firstname2, 'Boop', 20000);

        $this->userIdSwapper->swapIds($userId1->getId(), $userId2->getId());

        $updatedUserId1 = $this->entityManager->getRepository(User::class)->find($userId2->getId());
        $updatedUserId2 = $this->entityManager->getRepository(User::class)->find($userId1->getId());

        $this->assertSame($firstname1, $updatedUserId2->getFirstname());
        $this->assertSame($firstname2, $updatedUserId1->getFirstname());
    }

    private function createEntity(string $firstname, string $lastname, int $id): User
    {
        $entity = new User($id);
        $entity->setFirstname($firstname);
        $entity->setLastname($lastname);
        $entity->setNickname('nickname');
        $entity->setCafnumParent('');
        $entity->setTel('');
        $entity->setTel2('');
        $entity->setAdresse('');
        $entity->setCp('');
        $entity->setVille('');
        $entity->setPays('');
        $entity->setCiv('');
        $entity->setMoreinfo('');
        $entity->setCookietoken('');
        $entity->setNomadeParent(0);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }
}
