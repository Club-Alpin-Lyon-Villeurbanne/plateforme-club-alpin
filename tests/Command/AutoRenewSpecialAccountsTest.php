<?php

namespace App\Tests\Command;

use App\Command\AutoRenewSpecialAccounts;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AutoRenewSpecialAccountsTest extends WebTestCase
{
    public function testRenouvelleLesComptesSpeciauxAuPremierSeptembreDeLAnneeEnCours(): void
    {
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $compte = $this->signup();
        $compte->setJoinDate(new \DateTimeImmutable('2020-09-01'));
        $em->flush();

        $command = new AutoRenewSpecialAccounts(' ' . $compte->getId() . ',', $this->getContainer()->get(UserRepository::class), $em);
        $status = (new CommandTester($command))->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $em->clear();
        $this->assertSame(date('Y') . '-09-01', $em->find(User::class, $compte->getId())->getJoinDate()->format('Y-m-d'));
    }
}
