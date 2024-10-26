<?php

namespace App\Tests;

use App\Entity\UserAttr;
use App\Repository\CommissionRepository;
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

    public function testGetAllCommissionCodes()
    {
        $user = $this->signup();
        $this->signin($user);

        $this->createCommission();
        $tokenStorage = self::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'provider-test', $user->getRoles()));

        $userRights = self::getContainer()->get(UserRights::class);

        $commissionCodes = $userRights->getAllCommissionCodes();

        $this->assertIsArray($commissionCodes);
        $this->assertNotEmpty($commissionCodes);
        foreach ($commissionCodes as $code) {
            $this->assertIsValidCommission($code);
        }
    }

    public function testGetCommissionListForRight()
    {
        $user = $this->signup();
        $this->signin($user);

        $commission = $this->createCommission();
        $tokenStorage = self::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'provider-test', $user->getRoles()));

        $userRights = self::getContainer()->get(UserRights::class);

        $commissionCodes = $userRights->getCommissionListForRight('evt_validate');

        $this->assertIsArray($commissionCodes);
        $this->assertEmpty($commissionCodes);
        foreach ($commissionCodes as $code) {
            $this->assertIsValidCommission($code);
        }

        $this->addAttribute($user, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $commission->getCode());

        $commissionCodes = $userRights->getCommissionListForRight('evt_validate');
        $this->assertIsArray($commissionCodes);
        $this->assertNotEmpty($commissionCodes);
        foreach ($commissionCodes as $code) {
            $this->assertIsValidCommission($code);
        }

        $this->addAttribute($user, UserAttr::ADMINISTRATEUR);

        $commissionCodes = $userRights->getCommissionListForRight('evt_validate');
        $this->assertIsArray($commissionCodes);
        $this->assertNotEmpty($commissionCodes);
        foreach ($commissionCodes as $code) {
            $this->assertIsValidCommission($code);
        }
    }

    private function assertIsValidCommission($code)
    {
        $this->assertNotNull(self::getContainer()->get(CommissionRepository::class)->findOneBy(['code' => $code]), 'Asserting "' . $code . '" is valid commission code');
    }
}
