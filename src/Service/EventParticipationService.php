<?php

namespace App\Service;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Repository\EvtRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class EventParticipationService
{
    public function __construct(
        private Security $security,
        private EvtRepository $evtRepository,
        private EventParticipationMailService $mailer
    ) {
    }

    public function onBeforeAddParticipation(EventParticipation $participation): EventParticipation
    {
        if (EventParticipation::ROLE_INSCRIT !== $participation->getRole()) {
            throw new BadRequestHttpException('Le rôle ' . $participation->getRole() . ' n\'est pas supporté pour le moment');
        }
        if ($participation->getEvt()->getParticipation($this->getUser())) {
            throw new ConflictHttpException('Une participation à cet événement est déjà existante');
        }
        $this->ensureEventIsValid($participation->getEvt());
        if (\count($participation->getEvt()->getParticipations()) >= ($participation->getEvt()->getNgensMax() ?? 0)) {
            throw new BadRequestHttpException('Le nombre maximum de participants à cette sortie a été atteint');
        }
        if ($participation->getEvt()->isAutoAccept()) {
            $participation->setStatus(EventParticipation::STATUS_VALIDE);
        } else {
            $participation->setStatus(EventParticipation::STATUS_NON_CONFIRME);
        }
        $participation->setTsp(time());

        return $participation;
    }

    public function onAfterAddParticipation(EventParticipation $participation): void
    {
        $this->mailer->sendAddParticipationMailToSupervisors($participation);
        $this->mailer->sendAddParticipationMailToParticipant($participation);
    }

    public function onBeforeRemoveParticipation(EventParticipation $participation): void
    {
        $this->ensureEventIsValid($participation->getEvt());
        if (EventParticipation::STATUS_REFUSE === $participation->getStatus()) {
            throw new BadRequestHttpException('Cette participation a été refusée et ne peut être annulée');
        }
    }

    public function onAfterRemoveParticipation(EventParticipation $participation): void
    {
        // Notify
    }

    public function ensureEventIsValid(Evt $event)
    {
        if (Evt::STATUS_PUBLISHED_VALIDE !== $event->getStatus()) {
            throw new BadRequestHttpException('Cette sortie n\'est pas publiée');
        }
        $now = time();
        if (!$event->getJoinStart() || $now <= $event->getJoinStart()) {
            throw new BadRequestHttpException('Il n\'est pas encore possible de rejoindre cette sortie');
        }
        if (!$event->getJoinStart() || $now > $event->getTspEnd()) {
            throw new BadRequestHttpException('Il n\'est plus possible de rejoindre cette sortie');
        }
    }

    private function getUser(): User
    {
        $user = $this->security->getUser() instanceof User ? $this->security->getUser() : null;

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }
}
