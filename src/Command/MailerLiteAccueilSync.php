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
    /** Au-delà de ce volume sur une seule exécution, on suspecte une erreur de sélection. */
    private const VOLUME_GUARD = 800;

    /** Mois de bascule de la saison sportive. */
    private const SEASON_START_MONTH = 9;

    /**
     * Le dispositif demarre a la saison 2026-2027 : on ne rattrape pas les adherents
     * que le circuit casse depuis decembre 2025 a manques. Sans ce plancher, une
     * execution avant le 1er septembre 2026 selectionnerait les 2 400 licencies de
     * la saison precedente.
     */
    private const FIRST_SEASON = 2026;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MailerLiteService $mailerLite,
        private readonly LoggerInterface $logger,
        private readonly ?string $welcomeGroupId = null,
        private readonly ?string $renewalGroupId = null,
        private readonly string $deployEnv = 'development',
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    /**
     * La saison bascule le 1er septembre. Ce calcul est volontairement dérivé de la
     * date courante : rien n'est à reconfigurer d'une année sur l'autre.
     */
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

        // MailerLiteService ignore silencieusement les appels hors production : sans
        // ce garde-fou, --execute marquerait les adherents comme traites sans qu'aucun
        // mail ne parte, et ils ne seraient jamais repris.
        if ($execute && 'production' !== $this->deployEnv) {
            $output->writeln(sprintf('<comment>--execute ignore hors production (DEPLOY_ENV=%s) : bascule en dry-run.</comment>', $this->deployEnv));
            $execute = false;
        }

        if ($season < self::FIRST_SEASON) {
            $output->writeln(sprintf('<comment>Saison %d anterieure au demarrage du dispositif (%d) : rien a faire.</comment>', $season, self::FIRST_SEASON));

            return Command::SUCCESS;
        }

        // Ce depot sert plusieurs clubs et cron.json est partage, mais seul Lyon
        // utilise MailerLite. Un club sans configuration n'est pas en erreur :
        // renvoyer FAILURE ferait echouer son cron tous les matins, et un cron
        // rouge permanent finit par n'etre plus regarde du tout.
        if (!$this->welcomeGroupId || !$this->renewalGroupId) {
            $output->writeln('<comment>MailerLite non configure sur cette instance : rien a faire.</comment>');

            return Command::SUCCESS;
        }

        $candidates = $this->userRepository->findForAccueilCircuit($season);
        $output->writeln(sprintf('Saison %d — %d adherent(s) a traiter%s', $season, \count($candidates), $execute ? '' : ' [DRY-RUN]'));

        if (empty($candidates)) {
            return Command::SUCCESS;
        }

        if (\count($candidates) > self::VOLUME_GUARD && !$input->getOption('force')) {
            $output->writeln(sprintf('<error>%d candidats depassent le plafond de %d. Relancer avec --force apres verification.</error>', \count($candidates), self::VOLUME_GUARD));

            return Command::FAILURE;
        }

        $seasonStart = new \DateTimeImmutable($season . '-09-01 00:00:00');
        $buckets = [$this->welcomeGroupId => [], $this->renewalGroupId => []];

        foreach ($candidates as $user) {
            // Une fiche creee pendant la saison appartient a un nouveau licencie ;
            // une fiche anterieure a un renouvellement.
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

        foreach ($buckets as $groupId => $users) {
            if ([] === $users) {
                continue;
            }

            foreach ($users as $user) {
                // L'automation se declenche sur « subscriber_joins_group » : sans retrait
                // prealable, un adherent deja present ne recevrait rien.
                if (!$this->mailerLite->removeFromGroup((string) $user->getEmail(), (string) $groupId)) {
                    $this->logger->error('Circuit accueil : retrait de groupe impossible', ['email' => $user->getEmail(), 'groupId' => $groupId]);
                    \Sentry\captureMessage(sprintf('Circuit accueil : retrait impossible pour %s', $user->getEmail()));
                }
            }

            $results = $this->mailerLite->pushToGroup((string) $groupId, $users);
            $output->writeln(sprintf('  groupe %s : %d importe(s), %d echec(s), %d ignore(s)', $groupId, $results['imported'], $results['failed'], $results['skipped']));

            // L'API MailerLite ne renvoie qu'un compteur agrege, pas le detail par adresse :
            // on ne peut pas savoir qui, dans le groupe, est passe. Entre un doublon
            // d'envoi (visible, rattrapable) et un oubli silencieux, on choisit le doublon.
            if (0 === $results['failed']) {
                foreach ($users as $user) {
                    $marked[] = (int) $user->getId();
                }
            } else {
                \Sentry\captureMessage(sprintf('Circuit accueil : %d echec(s) sur le groupe %s', $results['failed'], $groupId));
            }
        }

        // Le marquage suit la confirmation de l'API : en cas d'echec, la personne
        // reste eligible et sera reprise a la prochaine execution.
        $this->userRepository->markAccueilSeason($marked, $season);
        $output->writeln(sprintf('%d adherent(s) marque(s) pour la saison %d.', \count($marked), $season));

        return Command::SUCCESS;
    }
}
