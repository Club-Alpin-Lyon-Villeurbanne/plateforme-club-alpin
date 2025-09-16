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
            dump($user->getFullName());
            $adhesionDate = (new \DateTime())->setTimestamp($user->getDateAdhesion());
            dump($adhesionDate);
            $endAdhesionDate = clone $adhesionDate;
            if ($adhesionDate->format('m') >= 9) {
                // adhésion entre septembre et décembre, la licence est valable jusqu'au 30 septembre (au soir) de l'année suivante
                $endAdhesionDate->modify('30 september next year');
            } else {
                // adhésion entre janvier et août, la licence est valable jusqu'au 30 septembre (au soir) de la même année
                $endAdhesionDate->modify('30 september this year');
            }
            $endAdhesionDate->modify('23:59:59');
            dump($endAdhesionDate);

            if ($endAdhesionDate < $eventEndDate) {
                $isLicenseValid = false;
            }
        } else {
            $isLicenseValid = false;
        }

        return $isLicenseValid;
    }
}
