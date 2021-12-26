<?php

namespace App\Command;

use App\Mailer\Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailTestCommand extends Command
{
    private Mailer $mailer;
    protected static $defaultName = 'mail:test';

    public function __construct(Mailer $mailer, string $name = null)
    {
        $this->mailer = $mailer;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require_once __DIR__.'/../../legacy/app/mailer/class.phpmailer.caf.php';

        $email = $input->getArgument('email');

        $this->mailer->send($email, 'transactional/test');

        return Command::SUCCESS;
    }
}
