<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailerLiteService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsCommand(name: 'mailerlite-accueil-sync', description: "Inscrit les adherents de la saison aux circuits d'accueil MailerLite")]
#[Autoconfigure]
class MailerLiteAccueilSync extends Command
{
    private const VOLUME_GUARD = 800;

    private const SEASON_START_MONTH = 9;

    private const FIRST_SEASON = 2026;

    private const REMOVE_DELAY_US = 500000;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MailerLiteService $mailerLite,
        private readonly LoggerInterface $logger,
        private readonly ?string $welcomeGroupId = null,
        private readonly ?string $renewalGroupId = null,
        private readonly string $deployEnv = 'development',
        private readonly ?string $apiKey = null,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    public static function seasonFor(\DateTimeInterface $date): int
    {
        $year = (int) $date->format('Y');

        return (int) $date->format('n') >= self::SEASON_START_MONTH ? $year : $year - 1;
    }

    protected function configure(): void
    {
        $this
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Effectuer reellement les envois (sinon dry-run)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Passer outre le plafond de volume')
            ->addOption('season', null, InputOption::VALUE_REQUIRED, 'Forcer la saison traitee (annee de septembre)')
            ->addOption('now', null, InputOption::VALUE_REQUIRED, 'Date de reference (tests)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $execute = (bool) $input->getOption('execute');
        $now = new \DateTimeImmutable((string) ($input->getOption('now') ?: 'now'));
        $season = null !== $input->getOption('season')
            ? (int) $input->getOption('season')
            : self::seasonFor($now);

        if ($execute && 'production' !== $this->deployEnv) {
            $output->writeln(sprintf('<comment>--execute ignore hors production (DEPLOY_ENV=%s) : bascule en dry-run.</comment>', $this->deployEnv));
            $execute = false;
        }

        if ($season < self::FIRST_SEASON) {
            $output->writeln(sprintf('<comment>Saison %d anterieure au demarrage du dispositif (%d) : rien a faire.</comment>', $season, self::FIRST_SEASON));

            return Command::SUCCESS;
        }

        if (!$this->apiKey && !$this->renewalGroupId) {
            $output->writeln('<comment>MailerLite non configure sur cette instance : rien a faire.</comment>');

            return Command::SUCCESS;
        }

        if (!$this->apiKey || !$this->welcomeGroupId || !$this->renewalGroupId || $this->welcomeGroupId === $this->renewalGroupId) {
            $message = sprintf(
                "Circuits d'accueil MailerLite : configuration invalide (bienvenue=%s, renouvellement=%s)",
                $this->welcomeGroupId ?: '(vide)',
                $this->renewalGroupId ?: '(vide)',
            );
            $output->writeln('<error>' . $message . '</error>');
            $this->logger->error($message);
            \Sentry\captureMessage($message);

            return Command::SUCCESS;
        }

        $candidates = $this->userRepository->findForAccueilCircuit($season);
        $output->writeln(sprintf('Saison %d — %d adherent(s) a traiter%s', $season, \count($candidates), $execute ? '' : ' [DRY-RUN]'));

        if ([] === $candidates) {
            $this->alertOnSilence($now, $season, $output);

            return Command::SUCCESS;
        }

        if (\count($candidates) > self::VOLUME_GUARD && !$input->getOption('force')) {
            $output->writeln(sprintf('<error>%d candidats depassent le plafond de %d. Relancer avec --force apres verification.</error>', \count($candidates), self::VOLUME_GUARD));
            \Sentry\captureMessage(sprintf("Circuits d'accueil MailerLite : %d candidats depassent le plafond de %d", \count($candidates), self::VOLUME_GUARD));

            return Command::FAILURE;
        }

        $seasonStart = new \DateTimeImmutable($season . '-09-01 00:00:00');
        $buckets = [$this->welcomeGroupId => [], $this->renewalGroupId => []];

        foreach ($candidates as $user) {
            $groupId = $user->getCreatedAt() >= $seasonStart ? $this->welcomeGroupId : $this->renewalGroupId;
            $buckets[$groupId][] = $user;
        }

        foreach ($buckets as $groupId => $users) {
            $output->writeln(sprintf('  groupe %s : %d', $groupId, \count($users)));
        }

        if (!$execute) {
            $output->writeln('Aucun envoi effectue (ajouter --execute).');

            return Command::SUCCESS;
        }

        $marked = [];
        $removalFailures = [];

        foreach ($buckets as $groupId => $users) {
            if ([] === $users) {
                continue;
            }

            foreach ($users as $user) {
                if (!$this->mailerLite->removeFromGroup((string) $user->getEmail(), (string) $groupId)) {
                    $this->logger->error('Circuit accueil : retrait de groupe impossible', ['email' => $user->getEmail(), 'groupId' => $groupId]);
                    \Sentry\captureMessage(sprintf('Circuit accueil : retrait impossible pour %s', $user->getEmail()));
                    $removalFailures[] = (int) $user->getId();
                }

                usleep(self::REMOVE_DELAY_US);
            }

            $results = $this->mailerLite->pushToGroup((string) $groupId, $users);
            $output->writeln(sprintf('  groupe %s : %d importe(s), %d echec(s), %d ignore(s)', $groupId, $results['imported'], $results['failed'], $results['skipped']));

            if (0 === $results['failed']) {
                foreach ($users as $user) {
                    if (!\in_array((int) $user->getId(), $removalFailures, true)) {
                        $marked[] = (int) $user->getId();
                    }
                }
            } else {
                \Sentry\captureMessage(sprintf('Circuit accueil : %d echec(s) sur le groupe %s', $results['failed'], $groupId));
            }
        }

        $this->userRepository->markAccueilSeason($marked, $season);
        $output->writeln(sprintf('%d adherent(s) marque(s) pour la saison %d.', \count($marked), $season));

        return Command::SUCCESS;
    }

    private function alertOnSilence(\DateTimeImmutable $now, int $season, OutputInterface $output): void
    {
        $month = (int) $now->format('n');
        $day = (int) $now->format('j');

        if (!\in_array($month, [9, 10], true) || (9 === $month && $day < 15)) {
            return;
        }

        if ($this->userRepository->countAccueilForSeason($season) > 0) {
            return;
        }

        $message = sprintf('Circuits d\'accueil MailerLite : aucun adherent traite pour la saison %d', $season);
        $output->writeln('<error>' . $message . '</error>');
        $this->logger->error($message);
        \Sentry\captureMessage($message);
    }
}
