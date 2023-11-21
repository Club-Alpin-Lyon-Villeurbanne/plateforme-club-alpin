<?php

namespace App\Command\Dev;

use App\Entity\User;
use App\Entity\Usertype;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FixturesLoadCommand extends Command
{
    private $container;
    protected static $defaultName = 'caf:fixtures:load';

    public function __construct(ContainerInterface $container, ?string $name = null)
    {
        parent::__construct($name);
        $this->container = $container;
    }

    protected function configure()
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Set user\'s email to run with the fixtures.')
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

        $email = $this->getUserEmail($input, $output);

        if (!$this->isEmailValid($email)) {
            throw new \RuntimeException("{$email} is not a valid email address.");
        }

        $fixtures = $this->addEmailToFixtures($fixtures, $email);

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

    private function addEmailToFixtures(array $fixtures, string $email): array
    {
        foreach ($fixtures as $fixture) {
            if ($fixture instanceof \DevData) {
                $fixture->setUserEmail($email);
            }
        }

        return $fixtures;
    }

    private function isEmailValid($email): bool
    {
        if (filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }

    protected function getUserEmail(InputInterface $input, OutputInterface $output): string
    {
        $email = $input->getArgument('email');

        if ('none' === $email) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter your email: ');
            $email = $helper->ask($input, $output, $question);
        }

        return $email;
    }
}
