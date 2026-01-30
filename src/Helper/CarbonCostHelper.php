<?php

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

    public function calculate(float $nbKm, int $nbPerson, int $nbVehicle, ?TransportModeEnum $transportMode): float
    {
        $rate = $this->rates[$transportMode?->value ?? TransportModeEnum::PUBLIC_TRAIN->value] ?? 0;

        $globalCost = $nbKm * $rate;
        if ($this->isCoefByPerson($transportMode)) {
            $cost = $globalCost * $nbPerson;
        } else {
            $cost = $globalCost * $nbVehicle;
        }

        return round($cost, 2);
    }

    protected function isCoefByPerson(?TransportModeEnum $transportMode): bool
    {
        $value = $transportMode?->value ?? TransportModeEnum::PUBLIC_TRAIN->value;

        return match ($value) {
            TransportModeEnum::PUBLIC_TRAIN->value, TransportModeEnum::PUBLIC_COACH->value, TransportModeEnum::PLANE->value => true,
            TransportModeEnum::DEDICATED_COACH->value, TransportModeEnum::THERMIC_CARPOOLING->value, TransportModeEnum::ELECTRIC_CARPOOLING->value, TransportModeEnum::BIKE_OR_WALK->value, TransportModeEnum::MINIVAN->value => false,
        };
    }
}
