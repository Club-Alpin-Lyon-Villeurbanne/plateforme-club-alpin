<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\UserAttr;
use App\Repository\UserRepository;
use App\Service\UserRightService;
use App\Tests\WebTestCase;

class UserRightControllerTest extends WebTestCase
{
    public function testAutoRemoveRightRequiresLogin(): void
    {
        $this->client->request('GET', '/retirer-responsabilite/auto/encadrant/ALPI');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAutoRemoveRightRemovesRightAndNotifies(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();

        $user = $this->signup();
        $this->signin($user);
        $commission = $this->createCommission('Alpinisme');
        $this->addAttribute($user, UserAttr::ENCADRANT, 'commission:' . $commission->getCode());

        $this->assertTrue($user->hasAttribute(UserAttr::ENCADRANT, $commission->getCode()));

        $mock = $this->createMock(UserRightService::class);
        $mock->expects($this->once())
            ->method('notify')
            ->with($this->isInstanceOf(UserAttr::class), 'suppression', $this->isInstanceOf(User::class))
        ;
        self::getContainer()->set(UserRightService::class, $mock);

        $this->client->request('GET', '/retirer-responsabilite/auto/encadrant/' . $commission->getCode());
        $this->assertResponseRedirects('/profil/infos.html');

        $repo = self::getContainer()->get(UserRepository::class);
        $em->clear();
        $fresh = $repo->find($user->getId());
        $this->assertFalse($fresh->hasAttribute(UserAttr::ENCADRANT, $commission->getCode()));

        $flashes = $this->getSession()->getFlashBag()->peek('success');
        $this->assertNotEmpty($flashes);
    }

    public function testAutoRemoveRightWithoutHavingItShowsError(): void
    {
        $user = $this->signup();
        $this->signin($user);
        $commission = $this->createCommission('Alpinisme');

        $mock = $this->createMock(UserRightService::class);
        $mock->expects($this->never())->method('notify');
        self::getContainer()->set(UserRightService::class, $mock);

        $this->client->request('GET', '/retirer-responsabilite/auto/encadrant/' . $commission->getCode());
        $this->assertResponseRedirects('/profil/infos.html');

        $errors = $this->getSession()->getFlashBag()->peek('error');
        $this->assertNotEmpty($errors);
    }
}
