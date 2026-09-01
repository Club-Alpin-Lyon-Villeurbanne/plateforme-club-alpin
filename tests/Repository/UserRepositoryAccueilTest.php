<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class UserRepositoryAccueilTest extends TestCase
{
    public function testLaRequeteDeSelectionAppliqueTousLesFiltres(): void
    {
        $dql = UserRepository::ACCUEIL_CIRCUIT_DQL;

        $this->assertStringContainsString('u.isDeleted = false', $dql);
        $this->assertStringContainsString('u.profileType = ' . User::PROFILE_CLUB_MEMBER, $dql);
        $this->assertStringContainsString('u.joinDate >= :seasonStart', $dql);
        $this->assertStringContainsString('u.radiationDate IS NULL', $dql);
        $this->assertStringContainsString('u.accueilSeason < :season', $dql);
        $this->assertStringContainsString("u.email NOT LIKE 'doublon.%'", $dql);
    }
}
