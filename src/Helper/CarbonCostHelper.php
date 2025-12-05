<?php

namespace App\Helper;

use App\Entity\TransportModeEnum;

class CarbonCostHelper
{
    protected array $rates = [];

    public function __construct(
        float $trainRate,
        float $thermicCoachRate,
        float $bikeRate,
        float $walkRate,
        float $thermicCarpoolingRate,
        float $electricCarpoolingRate,
        float $planeRate,
    ) {
        $this->rates = [
            TransportModeEnum::TRAIN->value => $trainRate,
            TransportModeEnum::THERMIC_COACH->value => $thermicCoachRate,
            TransportModeEnum::BIKE->value => $bikeRate,
            TransportModeEnum::WALK->value => $walkRate,
            TransportModeEnum::THERMIC_CARPOOLING->value => $thermicCarpoolingRate,
            TransportModeEnum::ELECTRIC_CARPOOLING->value => $electricCarpoolingRate,
            TransportModeEnum::PLANE->value => $planeRate,
        ];
    }

    public function calculate(float $nbKm, ?TransportModeEnum $transportMode): float
    {
        $rate = $this->rates[$transportMode?->value ?? TransportModeEnum::TRAIN->value] ?? 0;

        return round($nbKm * 2 * $rate, 2);
    }
}
