<?php

namespace App\Tests\Command\Dev;

use App\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class FichierAdherentCommandTest extends WebTestCase
{
    public function testExecute()
    {
        global $kernel;

        $kernel = $this->createKernel();
        $application = new Application($kernel);

        $command = $application->find('fichier-adherent');
        $commandTester = new CommandTester($command);

        $this->assertSame(0, $commandTester->execute(['command' => $command->getName()]));
    }
}
