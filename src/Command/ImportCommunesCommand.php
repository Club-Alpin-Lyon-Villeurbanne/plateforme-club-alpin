<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-communes',
    description: 'Import des communes françaises depuis l\'API La Poste (codes postaux + coordonnées GPS)',
)]
class ImportCommunesCommand extends Command
{
    private const API_URL = 'https://datanova.laposte.fr/data-fair/api/v1/datasets/laposte-hexasmal/lines';
    private const PAGE_SIZE = 1000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import des communes depuis l\'API La Poste');

        $this->connection->executeStatement('DELETE FROM communes');
        $io->comment('Table communes vidée.');

        $total = 0;
        $url = self::API_URL . '?size=' . self::PAGE_SIZE;

        do {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
            $results = $data['results'] ?? [];

            if (empty($results)) {
                break;
            }

            foreach ($results as $row) {
                $lat = 0;
                $long = 0;
                $geopoint = $row['_geopoint'] ?? '';
                if ($geopoint && str_contains($geopoint, ',')) {
                    [$lat, $long] = explode(',', $geopoint);
                }

                $this->connection->insert('communes', [
                    'code_commune_insee' => $row['code_commune_insee'] ?? '',
                    'nom_commune' => $row['nom_de_la_commune'] ?? '',
                    'code_postal' => $row['code_postal'] ?? '',
                    'libelle_acheminement' => $row['libelle_d_acheminement'] ?? '',
                    'ligne5' => null,
                    'geopoint' => $geopoint ?: null,
                    'latitude' => (float) $lat,
                    'longitude' => (float) $long,
                ]);
            }

            $total += \count($results);
            $url = $data['next'] ?? null;

            $io->write("\r  $total communes importées...");
        } while ($url);

        $io->newLine();
        $io->success("$total communes importées avec succès.");

        return Command::SUCCESS;
    }
}
