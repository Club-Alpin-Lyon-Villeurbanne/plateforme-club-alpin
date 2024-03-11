<?php

namespace App\Command;

use App\Mailer\Mailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsCommand(name: 'mail:test')]
class MailTestCommand extends Command
{
    private Mailer $mailer;

    public function __construct(Mailer $mailer, string $name = null)
    {
        $this->mailer = $mailer;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address.');
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        $this->mailer->send($email, 'transactional/test');

        return Command::SUCCESS;
    }
}
