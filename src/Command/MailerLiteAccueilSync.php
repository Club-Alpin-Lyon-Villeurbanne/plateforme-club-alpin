<?php

namespace App\Command;

use App\Entity\AccueilCircuitEnum;
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

    private const REMOVE_DELAY_US = 1000000;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MailerLiteService $mailerLite,
        private readonly LoggerInterface $logger,
        private readonly ?string $welcomeGroupId = null,
        private readonly ?string $renewalGroupId = null,
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
            ->addOption('circuit', null, InputOption::VALUE_REQUIRED, 'Circuit a traiter : nouveaux ou renouvellements')
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

        if (!$this->apiKey && !$this->renewalGroupId) {
            $output->writeln('<comment>MailerLite non configure sur cette instance : rien a faire.</comment>');

            return Command::SUCCESS;
        }

        $circuit = AccueilCircuitEnum::tryFrom((string) $input->getOption('circuit'));

        if (null === $circuit) {
            $output->writeln(sprintf('<error>Option --circuit obligatoire : %s.</error>', implode(' ou ', array_column(AccueilCircuitEnum::cases(), 'value'))));

            return Command::FAILURE;
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

        $groupId = match ($circuit) {
            AccueilCircuitEnum::NOUVEAUX => $this->welcomeGroupId,
            AccueilCircuitEnum::RENOUVELLEMENTS => $this->renewalGroupId,
        };
        $users = $this->userRepository->findForAccueilCircuit($season, $circuit);

        $output->writeln(sprintf('Saison %d — circuit %s (groupe %s) : %d adherent(s) a traiter%s', $season, $circuit->value, $groupId, \count($users), $execute ? '' : ' [DRY-RUN]'));

        if ([] === $users) {
            $this->alertOnSilence($now, $season, $circuit, $output);

            return Command::SUCCESS;
        }

        if (\count($users) > self::VOLUME_GUARD && !$input->getOption('force')) {
            $output->writeln(sprintf('<error>%d candidats depassent le plafond de %d. Relancer avec --force apres verification.</error>', \count($users), self::VOLUME_GUARD));
            \Sentry\captureMessage(sprintf("Circuit d'accueil MailerLite (%s) : %d candidats depassent le plafond de %d", $circuit->value, \count($users), self::VOLUME_GUARD));

            return Command::FAILURE;
        }

        if (!$execute) {
            $output->writeln('Aucun envoi effectue (ajouter --execute).');

            return Command::SUCCESS;
        }

        $marked = $this->syncGroup((string) $groupId, $users, $output);
        $this->userRepository->markAccueilSeason($marked, $season);
        $output->writeln(sprintf('%d adherent(s) marque(s) pour la saison %d.', \count($marked), $season));

        return Command::SUCCESS;
    }

    /**
     * @param User[] $users
     *
     * @return int[]
     */
    private function syncGroup(string $groupId, array $users, OutputInterface $output): array
    {
        $removalFailures = [];

        foreach ($users as $user) {
            if (!$this->mailerLite->removeFromGroup((string) $user->getEmail(), $groupId)) {
                $this->logger->error('Circuit accueil : retrait de groupe impossible', ['userId' => $user->getId(), 'groupId' => $groupId]);
                \Sentry\captureMessage(sprintf('Circuit accueil : retrait impossible pour l\'adherent %d', (int) $user->getId()));
                $removalFailures[] = (int) $user->getId();
            }

            usleep(self::REMOVE_DELAY_US);
        }

        $results = $this->mailerLite->pushToGroup($groupId, $users);
        $output->writeln(sprintf('  groupe %s : %d importe(s), %d echec(s), %d ignore(s)', $groupId, $results['imported'], $results['failed'], $results['skipped']));

        if (0 !== $results['failed']) {
            \Sentry\captureMessage(sprintf('Circuit accueil : %d echec(s) sur le groupe %s', $results['failed'], $groupId));

            return [];
        }

        $marked = [];

        foreach ($users as $user) {
            if (!\in_array((int) $user->getId(), $removalFailures, true)) {
                $marked[] = (int) $user->getId();
            }
        }

        return $marked;
    }

    private function alertOnSilence(\DateTimeImmutable $now, int $season, AccueilCircuitEnum $circuit, OutputInterface $output): void
    {
        $month = (int) $now->format('n');
        $day = (int) $now->format('j');

        if (!\in_array($month, [9, 10], true) || (9 === $month && $day < 15)) {
            return;
        }

        if ($this->userRepository->countAccueilForSeason($season, $circuit) > 0) {
            return;
        }

        $message = sprintf('Circuit d\'accueil MailerLite (%s) : aucun adherent traite pour la saison %d', $circuit->value, $season);
        $output->writeln('<error>' . $message . '</error>');
        $this->logger->error($message);
        \Sentry\captureMessage($message);
    }
}
