<?php

namespace App\Tests\Command;

use App\Command\AdminLogAnonymizationCronCommand;
use App\Tests\WebTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AdminLogAnonymizationCronCommandTest extends WebTestCase
{
    public function testAnonymiseLesLignesAnciennesEtGardeLActionDecrite(): void
    {
        $auteur = $this->signup();
        $id = $this->journaliser((int) $auteur->getId(), new \DateTimeImmutable('-13 months'));

        $status = $this->lancer('12');

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

        $this->lancer('12');

        $ligne = $this->lire($id);
        $this->assertSame((int) $auteur->getId(), (int) $ligne['user_log_admin']);
        $this->assertSame('2001:db8::1', $ligne['ip_log_admin']);
    }

    public static function dureesInvalides(): array
    {
        return [
            'nulle' => ['0'],
            'négative' => ['-1'],
            'décimale' => ['12.5'],
            'texte' => ['un an'],
        ];
    }

    #[DataProvider('dureesInvalides')]
    public function testRefuseUneDureeDeConservationInvalide(string $duree): void
    {
        $auteur = $this->signup();
        $id = $this->journaliser((int) $auteur->getId(), new \DateTimeImmutable('-13 months'));

        $status = $this->lancer($duree);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertSame((int) $auteur->getId(), (int) $this->lire($id)['user_log_admin']);
    }

    public static function seuils(): array
    {
        return [
            'jour présent dans le mois cible' => ['2027-03-15 07:38:00', 1, '2027-02-15 07:38:00'],
            'mois cible plus court' => ['2027-03-31 07:38:00', 1, '2027-02-28 07:38:00'],
            'mois cible bissextile' => ['2028-03-31 07:38:00', 1, '2028-02-29 07:38:00'],
            '29 février sur douze mois' => ['2028-02-29 07:38:00', 12, '2027-02-28 07:38:00'],
        ];
    }

    #[DataProvider('seuils')]
    public function testLeSeuilResteDansLeMoisCible(string $maintenant, int $mois, string $attendu): void
    {
        $seuil = AdminLogAnonymizationCronCommand::retentionLimit(new \DateTimeImmutable($maintenant), $mois);

        $this->assertSame($attendu, $seuil->format('Y-m-d H:i:s'));
    }

    private function lancer(string $dureeEnMois): int
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
