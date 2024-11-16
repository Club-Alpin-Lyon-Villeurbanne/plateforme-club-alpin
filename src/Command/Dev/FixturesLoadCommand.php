<?php

namespace App\Command\Dev;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand('caf:fixtures:load', 'Load a set of fixtures')]
class FixturesLoadCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[AutowireIterator('dev.data_fixtures')]
        private readonly iterable $fixtures,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fixtures = [...$this->fixtures];

        $em = $this->em;

        $this->em->getConnection()->executeQuery('DELETE FROM caf_article');
        $this->em->getConnection()->executeQuery('ALTER TABLE caf_article AUTO_INCREMENT = 1');
        $this->em->getConnection()->executeQuery('DELETE FROM caf_evt');
        $this->em->getConnection()->executeQuery('ALTER TABLE caf_evt AUTO_INCREMENT = 1');
        $this->em->getConnection()->executeQuery('DELETE FROM caf_commission');
        $this->em->getConnection()->executeQuery('ALTER TABLE caf_commission AUTO_INCREMENT = 1');
        $this->em->getConnection()->executeQuery('DELETE FROM caf_user');
        $this->em->getConnection()->executeQuery('ALTER TABLE caf_user AUTO_INCREMENT = 1');

        $executor = new ORMExecutor($em);
        $executor->setLogger(function ($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });

        $executor->execute($fixtures, $append = true);

        $em->flush();

        return 0;
    }
}
