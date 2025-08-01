<?php

namespace App\Command;

use App\Service\HelloAssoService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * cron à faire tourner chaque jour.
 */
#[AsCommand(name: 'ha-refresh-token')]
#[Autoconfigure]
class HelloAssoRefreshToken extends Command
{
    protected const int HELLO_ASSO_REFRESH_TOKEN_DURATION_IN_DAYS = 30;

    public function __construct(
        private readonly HelloAssoService $helloAssoService,
        private readonly LoggerInterface $logger,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTime();
        $tokenGetDate = $this->helloAssoService->getTokenGetDate();

        // 2j avant l'expiration, on renouvelle pour éviter de se retrouver sans token (et devoir refaire la mire d'autorisation)
        if (!$tokenGetDate || $tokenGetDate->diff($today)->d >= (self::HELLO_ASSO_REFRESH_TOKEN_DURATION_IN_DAYS - 2)) {
            $this->logger->info('Refresh token absent or expired: refresh it');
            try {
                $this->helloAssoService->getAccessTokenFromRefreshToken();
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return 0;
    }
}
