<?php

namespace App\Helper;

use App\Entity\EventParticipation;

class RoleHelper
{
    public function getParticipationRoleName(?EventParticipation $participation): string
    {
        if (!$participation) {
            return '';
        }

        return match ($participation->getRole()) {
            EventParticipation::ROLE_BENEVOLE => 'Bénévole d\'encadrement',
            EventParticipation::BENEVOLE => 'Participant bénévole',
            EventParticipation::ROLE_STAGIAIRE => 'Initiateur stagiaire',
            EventParticipation::ROLE_COENCADRANT => 'Co-encadrant',
            EventParticipation::ROLE_ENCADRANT => 'Encadrant',
            default => 'Participant',
        };
    }
}
