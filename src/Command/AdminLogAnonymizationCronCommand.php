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
        #[Autowire(env: 'int:ADMIN_LOG_RETENTION_MONTHS')]
        private readonly int $retentionMonths,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Une durée nulle ou négative anonymiserait tout le journal, y compris les actions du jour.
        if ($this->retentionMonths < 1) {
            $this->logger->error('Admin log anonymization: ADMIN_LOG_RETENTION_MONTHS must be at least 1');

            return Command::FAILURE;
        }

        $limit = (new \DateTimeImmutable(sprintf('-%d months', $this->retentionMonths)))->getTimestamp();

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
