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
    // On ne demande que les champs utiles : le dataset expose aussi `_contours_commune.geometry`
    // (MultiPolygon de plusieurs Ko/ligne) qui, sur 39 000 communes, sature la mémoire (OOM).
    private const SELECT_FIELDS = 'code_commune_insee,nom_de_la_commune,code_postal,libelle_d_acheminement,ligne_5,_geopoint';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 39 000 communes paginées par 1000 ; Sentry retient les spans HTTP par page,
        // ce qui peut saturer le 128M par défaut de la CLI Clever Cloud.
        ini_set('memory_limit', '512M');

        $io = new SymfonyStyle($input, $output);
        $io->title('Import des communes depuis l\'API La Poste');

        $this->connection->executeStatement('DELETE FROM communes');
        $io->comment('Table communes vidée.');

        $total = 0;
        $url = self::API_URL . '?size=' . self::PAGE_SIZE . '&select=' . self::SELECT_FIELDS;

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
                    'ligne5' => $row['ligne_5'] ?? null,
                    'geopoint' => $geopoint ?: null,
                    'latitude' => (float) $lat,
                    'longitude' => (float) $long,
                ]);
            }

            $total += \count($results);
            $url = $data['next'] ?? null;

            $io->write("\r  $total communes importées...");

            // Libère la réponse HTTP et ses éventuels spans Sentry/Cache.
            unset($response, $data, $results);
            gc_collect_cycles();
        } while ($url);

        $io->newLine();
        $io->success("$total communes importées avec succès.");

        return Command::SUCCESS;
    }
}
