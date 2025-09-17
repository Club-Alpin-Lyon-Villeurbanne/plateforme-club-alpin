<?php

namespace App\Command;

use App\Entity\Evt;
use App\Entity\UserAttr;
use App\Mailer\Mailer;
use App\Repository\EvtRepository;
use App\Repository\UserAttrRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'event-to-publish-reminder-cron',
    description: 'Cron de rappel des sorties en attention de publication'
)]
class EventToPublishCronCommand extends Command
{
    public function __construct(
        protected EvtRepository $eventRepository,
        protected UserAttrRepository $userAttrRepository,
        protected Mailer $mailer,
        protected LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Email reminder: find events to publish');
        $responsables = [];
        $eventsToPublish = $this->eventRepository->getAllEventsToPublish();
        $this->logger->info('Email reminder: ' . \count($eventsToPublish) . ' events to publish');

        // Pour chaque sortie non publiée
        /** @var Evt $event */
        foreach ($eventsToPublish as $event) {
            // on récupère les responsables de la commission liée...
            $commResp = $this->userAttrRepository->getResponsablesByCommission($event->getCommission());
            if (empty($commResp)) {
                $this->logger->error('Email reminder: no responsable for commission ' . $event->getCommission()->getTitle());
                continue;
            }

            /** @var UserAttr $userAttr */
            foreach ($commResp as $userAttr) {
                $responsables[$userAttr->getUser()->getEmail()]['responsable'] = $userAttr->getUser();
                $responsables[$userAttr->getUser()->getEmail()]['events'][] = $event;
            }
        }

        foreach ($responsables as $userEmail => $infos) {
            $this->mailer->send($infos['responsable'], 'transactional/rappel-sortie-a-valider-resp-commission', [
                'sorties' => $infos['events'],
            ]);
        }

        $this->logger->info('Email reminder: no (more) event to publish');

        return Command::SUCCESS;
    }
}
