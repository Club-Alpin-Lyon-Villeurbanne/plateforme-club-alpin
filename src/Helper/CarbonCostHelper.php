<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Evt;
use App\Entity\TransportModeEnum;

class CarbonCostHelper
{
    protected array $rates = [];

    public function __construct(
        float $publicTrainRate,
        float $publicCoachRate,
        float $dedicatedCoachRate,
        float $minivanRate,
        float $bikeWalkRate,
        float $thermicCarpoolingRate,
        float $electricCarpoolingRate,
    ) {
        $this->rates = [
            TransportModeEnum::PUBLIC_TRAIN->value => $publicTrainRate,
            TransportModeEnum::PUBLIC_COACH->value => $publicCoachRate,
            TransportModeEnum::DEDICATED_COACH->value => $dedicatedCoachRate,
            TransportModeEnum::MINIVAN->value => $minivanRate,
            TransportModeEnum::BIKE_OR_WALK->value => $bikeWalkRate,
            TransportModeEnum::THERMIC_CARPOOLING->value => $thermicCarpoolingRate,
            TransportModeEnum::ELECTRIC_CARPOOLING->value => $electricCarpoolingRate,
        ];
    }

    public function calculate(float $nbKm, int $nbPerson = 1, int $nbVehicules = 1, ?TransportModeEnum $transportMode = null): ?CarbonCost
    {
        if (null === $transportMode || $transportMode->isObsolete()) {
            return null;
        }

        $rate = $this->rates[$transportMode->value] ?? 0;
        $globalCost = $nbKm * $rate;

        if ($transportMode->requiresVehicleCount()) {
            $total = $globalCost * max(1, $nbVehicules);
        } else {
            $total = $globalCost * max(1, $nbPerson);
        }

        $perPerson = $total / max(1, $nbPerson);

        return new CarbonCost(
            total: round($total, 2),
            perPerson: round($perPerson, 2),
        );
    }

    /**
     * Recalcule et stocke le bilan carbone sur l'entité Evt selon son état courant
     * (nbKm, nb de participants validés, nb de véhicules, mode transport).
     *
     * À appeler à chaque mutation impactant le nombre de participants validés.
     */
    public function updateForEvent(Evt $event): void
    {
        $cost = $this->calculate(
            $event->getNbKm() ?: 0,
            $event->getParticipationsCount(),
            $event->getNbVehicules() ?: 1,
            $event->getModeTransport(),
        );
        $event->setCoutCarbone($cost?->total);
        $event->setCoutCarbonePerPerson($cost?->perPerson);
    }
}
