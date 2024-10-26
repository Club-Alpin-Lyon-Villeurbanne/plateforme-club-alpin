<?php

namespace App\Tests;

use App\Entity\UserAttr;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserRightsTest extends WebTestCase
{
    public function testHasRight()
    {
        $user = $this->signup();
        $this->signin($user);

        $commission = $this->createCommission();
        $tokenStorage = self::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'provider-test', $user->getRoles()));

        $userRights = self::getContainer()->get(UserRights::class);

        $this->assertFalse($userRights->allowed('evt_validate'));
        $this->assertFalse($userRights->allowed('evt_validate', 'commission:' . $commission->getCode()));
        $this->assertFalse($userRights->allowed('evt_validate_all'));

        $this->addAttribute($user, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $commission->getCode());

        $this->assertTrue($userRights->allowed('evt_validate'));
        $this->assertTrue($userRights->allowed('evt_validate', 'commission:' . $commission->getCode()));
        $this->assertFalse($userRights->allowed('evt_validate_all'));

        $this->addAttribute($user, UserAttr::ADMINISTRATEUR);

        $this->assertTrue($userRights->allowed('evt_validate'));
        $this->assertTrue($userRights->allowed('evt_validate', 'commission:' . $commission->getCode()));
        $this->assertTrue($userRights->allowed('evt_validate_all'));
    }
}
