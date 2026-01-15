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

        if (!$user->getJoinDate() instanceof \DateTimeImmutable) {
            return false;
        }

        $adhesionDate = $user->getJoinDate();
        $year = ($adhesionDate->format('m') >= self::LICENSE_TOLERANCY_PERIOD_END_MONTH) ? (int) $adhesionDate->format('Y') + 1 : $adhesionDate->format('Y');

        $endAdhesionDate = (clone $adhesionDate)->setTimestamp(strtotime("$year-" . self::LICENSE_TOLERANCY_PERIOD_END));
        if (User::PROFILE_DISCOVERY === $user->getProfileType() && $user->getDiscoveryEndDatetime()) {
            $endAdhesionDate = $user->getDiscoveryEndDatetime();
        }

        // on considère la date fin de sortie pour les sorties sur plusieurs jours
        $eventEndDate = $event->getEndDate();
        if ($endAdhesionDate < $eventEndDate) {
            $isLicenseValid = false;
        }

        // les cartes découvertes peuvent être prises en avance
        if (User::PROFILE_DISCOVERY === $user->getProfileType() && $user->getJoinDate() > $event->getStartDate()) {
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

    public function getLicenseExpirationDate(int $nbSeasonsToKeep = 2): ?\DateTime
    {
        $today = new \DateTime();
        $year = ($today->format('m') >= self::LICENSE_TOLERANCY_PERIOD_END_MONTH) ? (int) $today->format('Y') - 2 : $today->format('Y') - 3;

        return new \DateTime("$year-" . self::LICENSE_PERIOD_END);
    }
}
