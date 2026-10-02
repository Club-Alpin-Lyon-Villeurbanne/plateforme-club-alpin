<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'auto-renew-special-accounts',
    description: 'Cron de "renouvellement" automatique de licence des comptes spéciaux'
)]
class AutoRenewSpecialAccounts extends Command
{
    public function __construct(
        protected string $specialAccountsIds,
        protected UserRepository $userRepository,
        protected EntityManagerInterface $entityManager,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $specialAccountsIds = array_filter(array_map('trim', explode(',', $this->specialAccountsIds)));

        foreach ($specialAccountsIds as $id) {
            $user = $this->userRepository->find($id);
            if (!$user instanceof User) {
                $output->writeln("<error>Compte spécial $id introuvable</error>");
                continue;
            }

            $timestamp = mktime(1, 0, 0, 9, 1, date('Y'));
            $joinDate = (new \DateTimeImmutable())->setTimestamp($timestamp);

            $user->setJoinDate($joinDate);
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
