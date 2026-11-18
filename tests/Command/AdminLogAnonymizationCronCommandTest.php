<?php

namespace App\Tests\Command;

use App\Command\AdminLogAnonymizationCronCommand;
use App\Tests\WebTestCase;
use Doctrine\DBAL\Connection;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AdminLogAnonymizationCronCommandTest extends WebTestCase
{
    public function testAnonymiseLesLignesAnciennesEtGardeLActionDecrite(): void
    {
        $auteur = $this->signup();
        $id = $this->journaliser((int) $auteur->getId(), new \DateTimeImmutable('-13 months'));

        $status = $this->lancer(12);

        $this->assertSame(Command::SUCCESS, $status);
        $ligne = $this->lire($id);
        $this->assertNull($ligne['user_log_admin']);
        $this->assertNull($ligne['ip_log_admin']);
        $this->assertSame('edit-html', $ligne['code_log_admin']);
        $this->assertSame('Modif élément : « accueil »', $ligne['desc_log_admin']);
    }

    public function testNeTouchePasAuxLignesRecentes(): void
    {
        $auteur = $this->signup();
        $id = $this->journaliser((int) $auteur->getId(), new \DateTimeImmutable('-11 months'));

        $this->lancer(12);

        $ligne = $this->lire($id);
        $this->assertSame((int) $auteur->getId(), (int) $ligne['user_log_admin']);
        $this->assertSame('2001:db8::1', $ligne['ip_log_admin']);
    }

    public function testRefuseUneDureeDeConservationNulle(): void
    {
        $auteur = $this->signup();
        $id = $this->journaliser((int) $auteur->getId(), new \DateTimeImmutable('-13 months'));

        $status = $this->lancer(0);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertSame((int) $auteur->getId(), (int) $this->lire($id)['user_log_admin']);
    }

    private function lancer(int $dureeEnMois): int
    {
        $command = new AdminLogAnonymizationCronCommand($this->connexion(), new NullLogger(), $dureeEnMois);

        return (new CommandTester($command))->execute([]);
    }

    private function journaliser(int $idUtilisateur, \DateTimeImmutable $date): int
    {
        $this->connexion()->insert('caf_log_admin', [
            'code_log_admin' => 'edit-html',
            'desc_log_admin' => 'Modif élément : « accueil »',
            'ip_log_admin' => '2001:db8::1',
            'date_log_admin' => $date->getTimestamp(),
            'user_log_admin' => $idUtilisateur,
        ]);

        return (int) $this->connexion()->lastInsertId();
    }

    private function lire(int $id): array
    {
        return $this->connexion()->fetchAssociative('SELECT * FROM caf_log_admin WHERE id_log_admin = ?', [$id]);
    }

    private function connexion(): Connection
    {
        return $this->getContainer()->get(Connection::class);
    }
}
