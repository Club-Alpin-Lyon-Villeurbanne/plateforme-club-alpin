<?php

declare(strict_types=1);

namespace App\Helper;

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
        float $planeRate,
    ) {
        $this->rates = [
            TransportModeEnum::PUBLIC_TRAIN->value => $publicTrainRate,
            TransportModeEnum::PUBLIC_COACH->value => $publicCoachRate,
            TransportModeEnum::DEDICATED_COACH->value => $dedicatedCoachRate,
            TransportModeEnum::MINIVAN->value => $minivanRate,
            TransportModeEnum::BIKE_OR_WALK->value => $bikeWalkRate,
            TransportModeEnum::THERMIC_CARPOOLING->value => $thermicCarpoolingRate,
            TransportModeEnum::ELECTRIC_CARPOOLING->value => $electricCarpoolingRate,
            TransportModeEnum::PLANE->value => $planeRate,
        ];
    }

    public function calculate(float $nbKm, int $nbPerson = 1, int $nbVehicules = 1, ?TransportModeEnum $transportMode = null): ?float
    {
        if (null === $transportMode) {
            return null;
        }

        $rate = $this->rates[$transportMode->value] ?? 0;

        $globalCost = $nbKm * $rate;
        if ($this->isCoefByPerson($transportMode)) {
            $cost = $globalCost * max(1, $nbPerson);
        } else {
            $cost = $globalCost * max(1, $nbVehicules);
        }

        return round($cost, 2);
    }

    protected function isCoefByPerson(TransportModeEnum $transportMode): bool
    {
        return match ($transportMode) {
            TransportModeEnum::PUBLIC_TRAIN, TransportModeEnum::PUBLIC_COACH, TransportModeEnum::PLANE => true,
            TransportModeEnum::DEDICATED_COACH, TransportModeEnum::THERMIC_CARPOOLING, TransportModeEnum::ELECTRIC_CARPOOLING, TransportModeEnum::BIKE_OR_WALK, TransportModeEnum::MINIVAN => false,
        };
    }
}
