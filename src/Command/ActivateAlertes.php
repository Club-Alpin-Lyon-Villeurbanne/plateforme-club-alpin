<?php

namespace App\Command;

use App\Utils\EmailAlerts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsCommand(name: 'activate-alerts')]
#[Autoconfigure]
class ActivateAlertes extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->executeQuery('UPDATE caf_user SET alerts = :alerts WHERE alerts IS NULL OR JSON_TYPE(alerts) = \'ARRAY\'', [
            'alerts' => EmailAlerts::DEFAULT_ALERTS_JSON,
        ]);

        return 0;
    }
}
