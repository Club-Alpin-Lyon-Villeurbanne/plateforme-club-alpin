<?php

declare(strict_types=1);

namespace App\Command;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ffcam:renewal-repair',
    description: "Répare le flag 'doit_renouveler_user' pour les adhérents expirés non correctement flaggés",
)]
class RepairRenewalFlagsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche le nombre de comptes impactés sans mise à jour')
            ->addOption('cutoff-year', null, InputOption::VALUE_REQUIRED, "Année de référence pour la coupure saison (par défaut: année courante)");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $year = $input->getOption('cutoff-year');
        $year = is_numeric($year) ? (int) $year : (int) (new DateTimeImmutable())->format('Y');

        // Coupure à la fin de la saison précédente: 31/08/(année-1)
        $cutoff = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%d-08-31 00:00:00', $year - 1));
        if (false === $cutoff) {
            $io->error('Date de coupure invalide.');
            return Command::FAILURE;
        }

        $params = [
            'cutoff' => $cutoff->getTimestamp(),
            'now' => time(),
        ];

        // Condition commune: comptes actifs non manuels/nomades/admin et alerte FFCAM positionnée
        $where = <<<SQL
is_deleted = 0
AND nomade_user = 0
AND manuel_user = 0
AND id_user != 1
AND alerte_renouveler_user = 1
AND doit_renouveler_user = 0
AND (
  date_adhesion_user IS NULL
  OR date_adhesion_user <= :cutoff
)
SQL;

        // Preview
        $countSql = 'SELECT COUNT(*) AS nb FROM caf_user WHERE ' . $where;
        $count = (int) $this->connection->fetchOne($countSql, $params);

        $io->title("Réparation du flag 'doit_renouveler_user'");
        $io->text(sprintf('Coupure utilisée: 31/08/%d (timestamp: %d)', $year - 1, $params['cutoff']));
        $io->text(sprintf('Comptes impactés: %d', $count));

        if (0 === $count) {
            $io->success('Aucun compte à corriger.');
            return Command::SUCCESS;
        }

        if ($input->getOption('dry-run')) {
            $io->warning('Dry-run activé: aucune modification écrite.');
            return Command::SUCCESS;
        }

        $updateSql = 'UPDATE caf_user SET doit_renouveler_user = 1, ts_update_user = :now WHERE ' . $where;
        $updated = $this->connection->executeStatement($updateSql, $params);

        $io->success(sprintf('%d compte(s) corrigé(s).', $updated));

        return Command::SUCCESS;
    }
}

