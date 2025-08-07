<?php

namespace App\Service;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Mailer\Mailer;

class EventParticipationMailService
{
    public function __construct(private Mailer $mailer, private string $baseUrl)
    {
   
    }

    public function sendAddParticipationMailToSupervisors(EventParticipation $participation): void
    {
       $supervisorsParticipations = $participation->getEvent()->getParticipations([EventParticipation::ROLE_ENCADRANT, EventParticipation::ROLE_COENCADRANT, EventParticipation::ROLE_STAGIAIRE]);

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
                        'profile_url' => $this->baseUrl . '/user-full/' . $user->getId() . '.html',
                    ];
                }, [$participation->getUser()]),
                'firstname' => ucfirst($supervisor->getFirstname()),
                'lastname' => strtoupper($supervisor->getLastname()),
                'nickname' => $supervisor->getNickname(),
                'message' => '',
                'covoiturage' => false,
                'dest_role' => $supervisorParticipation->getRole() ?? 'l\'auteur',
            ], [], null, $participation->getUser()->getEmail());
       }
    }

    public function sendAddParticipationMailToParticipant(EventParticipation $participation): void
    {
        if($participation->getEvent()->isAutoAccept()) {
            $this->mailer->send($participation->getUser()->getEmail(), 'transactional/sortie-participation-confirmee', [
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
                        'firstname' => ucfirst($participation->getUser()->getFirstname()),
                        'lastname' => strtoupper($participation->getUser()->getLastname()),
                        'nickname' => $participation->getUser()->getNickname(),
                        'email' => $participation->getUser()->getEmail(),
                    ],
                ],
                'covoiturage' => false,
            ]);
        }
    }

    function getEventUrl(Evt $event): string
    {
        return $this->baseUrl . '/sortie/' . $event->getCode() . '-' . $event->getId() . '.html';
    }
}