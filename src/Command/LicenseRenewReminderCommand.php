<?php

namespace App\Command;

use App\Mailer\Mailer;
use App\Repository\EvtRepository;
use App\Service\UserLicenseChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'license-renew-reminder-cron',
    description: 'Cron de rappel de renouvellement de licence',
)]
class LicenseRenewReminderCommand extends Command
{
    protected const int DAYS_BEFORE_EVENT = 7;

    public function __construct(
        protected EvtRepository $eventRepository,
        protected UserLicenseChecker $licenseChecker,
        protected Mailer $mailer,
        protected UrlGeneratorInterface $urlGenerator,
        protected LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // trouver les sorties qui commencent dans x jours (DAYS_BEFORE_EVENT)
        $events = $this->eventRepository->getUpcomingEvents(null, ['limit' => 10000, 'start_in_days' => self::DAYS_BEFORE_EVENT]);

        // trouver leurs participants
        foreach ($events as $event) {
            $notifyOrganizer = false;
            $participants = $event->getParticipations();
            foreach ($participants as $participation) {
                $participant = $participation->getUser();

                // vérifier si la licence de chaque participant est à renouveler
                if (!$this->licenseChecker->isLicenseValidForEvent($participant, $event)) {
                    $notifyOrganizer = true;

                    // envoyer un email de rappel au participant si nécessaire
                    try {
                        $this->mailer->send($participant, 'transactional/licence-expiree-participant', [
                            'event_name' => $event->getTitre(),
                            'event_url' => $this->urlGenerator->generate('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                            'event_date' => date('d/m/Y', $event->getTsp()),
                        ]);
                    } catch (\Exception $exception) {
                        $this->logger->error('Erreur lors de l\'envoi du mail de rappel de renouvellement de licence au participant ' . $participant->getId() . ' pour la sortie ' . $event->getId());
                        $this->logger->error($exception->getMessage());
                    }
                }
            }

            // prévenir l'encadrement si nécessaire
            if ($notifyOrganizer) {
                // liste des encadrants
                $destinataires = [];
                $destinataires[] = $event->getUser();
                foreach ($event->getEncadrants() as $encadrant) {
                    $destinataires[] = $encadrant->getUser();
                }
                try {
                    $this->mailer->send($destinataires, 'transactional/licence-expiree-encadrement', [
                        'event_name' => $event->getTitre(),
                        'event_url' => $this->urlGenerator->generate('sortie', ['code' => $event->getCode(), 'id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'event_date' => date('d/m/Y', $event->getTsp()),
                    ]);
                } catch (\Exception $exception) {
                    $this->logger->error('Erreur lors de l\'envoi du mail de licence expirée à l\'organisateur de la sortie ' . $event->getId());
                    $this->logger->error($exception->getMessage());
                }
            }
        }

        return Command::SUCCESS;
    }
}
