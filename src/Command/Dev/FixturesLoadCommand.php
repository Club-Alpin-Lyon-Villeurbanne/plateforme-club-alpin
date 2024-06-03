<?php

namespace App\Command\Dev;

use App\Entity\User;
use App\Entity\Usertype;
use App\Repository\CommissionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\DataFixtures\DevData;

#[AsCommand(name: 'caf:fixtures:load')]
class FixturesLoadCommand extends Command
{
    private ContainerInterface $container;
    public function __construct(ContainerInterface $container, ?string $name = null)
    {
        parent::__construct($name);
        $this->container = $container;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->container->get('doctrine')->getManager();
        $commissionRepository = $this->container->get(CommissionRepository::class);
        (new DevData($commissionRepository))->load($em);

        $userType = $em
            ->getRepository(Usertype::class)
            ->findOneByCode('developpeur');
        $mainUser = $em
            ->getRepository(User::class)
            ->findOneByEmail('test@clubalpinlyon.fr')
            ->addAttribute($userType);
        $em->flush();

        return 0;
    }
}
