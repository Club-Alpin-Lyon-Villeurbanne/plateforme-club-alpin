<?php

namespace App\Command\Dev;

use App\Entity\User;
use App\Entity\Usertype;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsCommand(name: 'caf:fixtures:load')]
class FixturesLoadCommand extends Command
{
    private ContainerInterface $container;
    public function __construct(ContainerInterface $container, ?string $name = null)
    {
        parent::__construct($name);
        $this->container = $container;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('paths', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The path(s) of the fixtures')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paths = $input->getArgument('paths');

        $loader = new ContainerAwareLoader($this->container);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            } elseif (is_file($path)) {
                $loader->loadFromFile($path);
            }
        }
        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new \InvalidArgumentException('Could not find any fixtures to load in: '."\n\n- ".implode("\n- ", $paths));
        }

        $em = $this->container->get('doctrine')->getManager();

        $userType = $em
            ->getRepository(Usertype::class)
            ->findOneByCode('developpeur');
        $mainUser = $em
            ->getRepository(User::class)
            ->findOneByEmail('test@clubalpinlyon.fr')
            ->addAttribute($userType);
        $em->flush();

//        $purger = new ORMPurger($em);
//        $purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($em); // , $purger);
        $executor->setLogger(function ($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });

        $executor->execute($fixtures, true);

        return 0;
    }
}
