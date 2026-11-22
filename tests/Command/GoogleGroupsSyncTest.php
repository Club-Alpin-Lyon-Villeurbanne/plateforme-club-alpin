<?php

namespace App\Tests\Command;

use App\Command\GoogleGroupsSync;
use PHPUnit\Framework\TestCase;

class GoogleGroupsSyncTest extends TestCase
{
    public function testGmailIgnoreLesPoints(): void
    {
        $this->assertSame(
            GoogleGroupsSync::comparableEmail('Prenom.Nom@gmail.com'),
            GoogleGroupsSync::comparableEmail('prenomnom@gmail.com'),
        );
    }

    public function testLesPointsComptentHorsGmail(): void
    {
        $this->assertNotSame(
            GoogleGroupsSync::comparableEmail('prenom.nom@orange.fr'),
            GoogleGroupsSync::comparableEmail('prenomnom@orange.fr'),
        );
    }
}
