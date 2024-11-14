<?php

namespace App\Tests\Command;

use App\Tests\TestHelpers\FfcamTestHelper;
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

        $filePath = FfcamTestHelper::generateFile([
            [
                'cafnum' => rand(100000000000, 999999999999),
                'lastname' => 'DUPONT',
                'firstname' => 'JEAN',
            ],
            [
                'cafnum' => rand(100000000000, 999999999999),
                'lastname' => 'MARTIN',
                'firstname' => 'PIERRE',
            ],
        ]);

        $this->assertSame(0, $commandTester->execute(['command' => $command->getName(), 'file-path' => $filePath]));
    }
}
