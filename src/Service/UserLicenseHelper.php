<?php

namespace App\Service;

use App\Entity\Evt;
use App\Entity\User;

class UserLicenseHelper
{
    public const string LICENSE_TOLERANCY_PERIOD_END = '09-30 23:59:59'; // 30 septembre
    protected const string LICENSE_PERIOD_END = '08-31 23:59:59'; // 31 aout
    protected const int LICENSE_TOLERANCY_PERIOD_END_MONTH = 9; // septembre

    public function isLicenseValidForEvent(User $user, Evt $event): bool
    {
        $isLicenseValid = true;

        if (!$user->getDateAdhesion()) {
            return false;
        }

        $adhesionDate = (new \DateTime())->setTimestamp($user->getDateAdhesion());
        $year = ($adhesionDate->format('m') >= self::LICENSE_TOLERANCY_PERIOD_END_MONTH) ? (int) $adhesionDate->format('Y') + 1 : $adhesionDate->format('Y');

        $endAdhesionDate = clone $adhesionDate;
        $endAdhesionDate->setTimestamp(strtotime("$year-" . self::LICENSE_TOLERANCY_PERIOD_END));

        // on considère la date fin de sortie pour les sorties sur plusieurs jours
        $eventEndDate = (new \DateTime())->setTimestamp($event->getTspEnd());
        if ($endAdhesionDate < $eventEndDate) {
            $isLicenseValid = false;
        }

        return $isLicenseValid;
    }

    public function getLicenseExpirationTimestamp(): int
    {
        $today = new \DateTime();
        $year = ($today->format('m') <= self::LICENSE_TOLERANCY_PERIOD_END_MONTH) ? (int) $today->format('Y') - 1 : $today->format('Y');

        return (int) strtotime("$year-" . self::LICENSE_PERIOD_END);
    }
}
