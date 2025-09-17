<?php

namespace App\Service;

use App\Entity\Evt;
use App\Entity\User;

class UserLicenseChecker
{
    public function isLicenseValidForEvent(User $user, Evt $event): bool
    {
        $isLicenseValid = true;

        // on considère la date fin de sortie pour les sorties sur plusieurs jours
        $eventEndDate = (new \DateTime())->setTimestamp($event->getTspEnd());
        if ($user->getDateAdhesion()) {
            $adhesionDate = (new \DateTime())->setTimestamp($user->getDateAdhesion());
            $year = ($adhesionDate->format('m') >= 9) ? (int) $adhesionDate->format('Y') + 1 : $adhesionDate->format('Y');
            $endAdhesionDate = clone $adhesionDate;
            $endAdhesionDate->setDate($year, 9, 30)->setTime(23, 59, 59);

            if ($endAdhesionDate < $eventEndDate) {
                $isLicenseValid = false;
            }
        } else {
            $isLicenseValid = false;
        }

        return $isLicenseValid;
    }
}
