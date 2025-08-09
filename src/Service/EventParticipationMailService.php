<?php

namespace App\Service;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Mailer\Mailer;
use Symfony\Bundle\SecurityBundle\Security;

class EventParticipationMailService
{
    public function __construct(private Mailer $mailer, private string $baseUrl, private Security $security)
    {
    }

    public function getUser(EventParticipation $participation): User
    {
        return $this->security->getUser() ?? $participation->getUser();
    }

    public function sendAddParticipationMailToSupervisors(EventParticipation $participation): void
    {
        $supervisorsParticipations = $participation->getEvent()->getEncadrants();

        foreach ($supervisorsParticipations as $supervisorParticipation) {
            $supervisor = $supervisorParticipation->getUser();
            $this->mailer->send($supervisor->getEmail(), 'transactional/sortie-demande-inscription', [
                'role' => $participation->getRole(),
                'event_name' => $participation->getEvent()->getTitre(),
                'event_url' => $this->getEventUrl($participation->getEvent()),
                'event_date' => date('d/m/Y', $participation->getEvent()->getTsp()),
                'auto_accept' => $participation->getEvent()->isAutoAccept(),
                'commission' => $participation->getEvent()->getCommission()->getTitle(),
                'inscrits' => array_map(function ($user) {
                    return [
                        'firstname' => ucfirst($user->getFirstname()),
                        'lastname' => strtoupper($user),
                        'nickname' => $user->getNickname(),
                        'email' => $user->getEmail(),
                        'profile_url' => $this->getUserProfileUrl($user),
                    ];
                }, [$participation->getUser()]),
                'firstname' => ucfirst($this->getUser($participation)->getFirstname()),
                'lastname' => strtoupper($this->getUser($participation)->getLastname()),
                'nickname' => $this->getUser($participation)->getNickname(),
                'message' => '',
                'covoiturage' => false,
                'dest_role' => $supervisorParticipation->getRole() ?? 'l\'auteur',
            ], [], null, $this->getUser($participation)->getEmail());
        }
    }

    public function sendAddParticipationMailToParticipant(EventParticipation $participation): void
    {
        if ($participation->getEvent()->isAutoAccept()) {
            $this->mailer->send($this->getUser($participation), 'transactional/sortie-participation-confirmee', [
                'role' => $participation->getRole(),
                'event_name' => $participation->getEvent()->getTitre(),
                'event_url' => $this->getEventUrl($participation->getEvent()),
                'event_date' => date('d/m/Y', $participation->getEvent()->getTsp()),
                'commission' => $participation->getEvent()->getCommission()->getTitle(),
            ]);
        } else {
            $this->mailer->send($participation->getUser()->getEmail(), 'transactional/sortie-demande-inscription-confirmation', [
                'role' => $participation->getRole(),
                'event_name' => $participation->getEvent()->getTitre(),
                'event_url' => $this->getEventUrl($participation->getEvent()),
                'event_date' => date('d/m/Y', $participation->getEvent()->getTsp()),
                'commission' => $participation->getEvent()->getCommission()->getTitle(),
                'inscrits' => [
                    [
                        'firstname' => ucfirst($this->getUser($participation)->getFirstname()),
                        'lastname' => strtoupper($this->getUser($participation)->getLastname()),
                        'nickname' => $this->getUser($participation)->getNickname(),
                        'email' => $this->getUser($participation)->getEmail(),
                    ],
                ],
                'covoiturage' => false,
            ]);
        }
    }

    public function sendRemoveParticipationMailToSupervisors(EventParticipation $participation, ?string $reason): void
    {
        $user = $this->getUser($participation);
        $event = $participation->getEvt();
        $supervisors = $event->getEncadrants();
        foreach ($supervisors as $supervisor) {
            $this->mailer->send($supervisor->getUser(), 'transactional/sortie-desinscription', [
                'username' => $participation->getUser()->getFirstname() . ' ' . $participation->getUser()->getLastname(),
                'event_url' => $this->getEventUrl($event),
                'event_name' => $event->getTitre(),
                'commission' => $event->getCommission()->getTitle(),
                'event_date' => $event->getTsp() ? date('d/m/Y', $event->getTsp()) : '',
                'reason_explanation' => $reason ?? '',
                'user' => $user,
                'profile_url' => $this->getUserProfileUrl($user),
            ], [], null, $user->getEmail());
        }
    }

    private function getEventUrl(Evt $event): string
    {
        return $this->baseUrl . '/sortie/' . $event->getCode() . '-' . $event->getId() . '.html';
    }

    private function getUserProfileUrl(User $user): string
    {
        return $this->baseUrl . '/user-full/' . $user->getId() . '.html';
    }
}
