<?php

namespace App\Command;

use App\Repository\UserNotificationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsCommand(name: 'clean-alerts')]
#[Autoconfigure]
class CleanAlertes extends Command
{
    public function __construct(
        private readonly UserNotificationRepository $userNotificationRepository,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->userNotificationRepository->deleteExpiredNotifications();

        return 0;
    }
}
