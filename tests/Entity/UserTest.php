<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testAccueilSeasonParDefautAZero(): void
    {
        $user = new User();

        $this->assertSame(0, $user->getAccueilSeason());
    }

    public function testAccueilSeasonEstModifiable(): void
    {
        $user = new User();
        $user->setAccueilSeason(2026);

        $this->assertSame(2026, $user->getAccueilSeason());
    }
}
