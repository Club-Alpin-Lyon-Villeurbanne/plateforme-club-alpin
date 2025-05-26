<?php

namespace App\Service;

use App\Entity\Commission;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Repository\UserAttrRepository;

class ParticipantService
{
    public function __construct(
        protected UserAttrRepository $userAttrRepository,
        protected array $encadrants = [],
        protected array $initiateurs = [],
        protected array $coencadrants = [],
        protected array $benevoles = [],
        protected array $currentEncadrants = [],
        protected array $currentInitiateurs = [],
        protected array $currentCoencadrants = [],
        protected array $currentBenevoles = [],
    ) {
    }

    public function buildManagersLists(?Commission $commission, ?Evt $event): void
    {
        $rolesMap = [
            EventParticipation::ROLE_ENCADRANT => [
                'global' => 'encadrants',
                'current' => 'currentEncadrants',
            ],
            EventParticipation::ROLE_STAGIAIRE => [
                'global' => 'initiateurs',
                'current' => 'currentInitiateurs',
            ],
            EventParticipation::ROLE_COENCADRANT => [
                'global' => 'coencadrants',
                'current' => 'currentCoencadrants',
            ],
            EventParticipation::ROLE_BENEVOLE => [
                'global' => 'benevoles',
                'current' => 'currentBenevoles',
            ],
        ];

        if ($commission instanceof Commission) {
            foreach ($rolesMap as $role => $properties) {
                foreach ($this->userAttrRepository->listAllEncadrants($commission, [$role]) as $attribute) {
                    $this->{$properties['global']}[$attribute->getUser()->getId()] = $attribute->getUser()->getFullName();
                }

                if ($event instanceof Evt) {
                    foreach ($event->getParticipations([$role], null) as $participant) {
                        $this->{$properties['current']}[] = $participant->getUser()->getId();
                    }
                }
            }
        }
    }

    public function getEncadrants(): array
    {
        return $this->encadrants;
    }

    public function getInitiateurs(): array
    {
        return $this->initiateurs;
    }

    public function getCoencadrants(): array
    {
        return $this->coencadrants;
    }

    public function getBenevoles(): array
    {
        return $this->benevoles;
    }

    public function getCurrentEncadrants(): array
    {
        return $this->currentEncadrants;
    }

    public function getCurrentInitiateurs(): array
    {
        return $this->currentInitiateurs;
    }

    public function getCurrentCoencadrants(): array
    {
        return $this->currentCoencadrants;
    }

    public function getCurrentBenevoles(): array
    {
        return $this->currentBenevoles;
    }
}
