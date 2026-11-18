<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'admin-log-anonymization-cron',
    description: 'Retire l\'utilisateur et l\'adresse IP des lignes anciennes du journal d\'administration'
)]
class AdminLogAnonymizationCronCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'ADMIN_LOG_RETENTION_MONTHS')]
        private readonly string $retentionMonths,
    ) {
        parent::__construct();
    }

    /**
     * Même jour du mois cible, ou son dernier jour s'il est plus court : « -1 month » le 31 mars
     * donnerait le 3 mars et anonymiserait des lignes trop récentes.
     */
    public static function retentionLimit(\DateTimeImmutable $now, int $months): \DateTimeImmutable
    {
        $targetMonth = $now->modify('first day of this month')->modify(sprintf('-%d months', $months));
        $day = min((int) $now->format('j'), (int) $targetMonth->format('t'));

        return $targetMonth->setDate((int) $targetMonth->format('Y'), (int) $targetMonth->format('n'), $day);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Une durée invalide pourrait anonymiser trop de lignes, y compris les actions du jour.
        $months = filter_var($this->retentionMonths, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (false === $months) {
            $this->logger->error('Admin log anonymization: ADMIN_LOG_RETENTION_MONTHS must be a positive integer');

            return Command::FAILURE;
        }

        $limit = self::retentionLimit(new \DateTimeImmutable(), $months)->getTimestamp();

        $anonymized = $this->connection->executeStatement(
            'UPDATE caf_log_admin
                SET user_log_admin = NULL, ip_log_admin = NULL
                WHERE date_log_admin < :limit
                AND (user_log_admin IS NOT NULL OR ip_log_admin IS NOT NULL)',
            ['limit' => $limit]
        );

        $this->logger->info('Admin log anonymization: ' . $anonymized . ' rows anonymized');

        return Command::SUCCESS;
    }
}
