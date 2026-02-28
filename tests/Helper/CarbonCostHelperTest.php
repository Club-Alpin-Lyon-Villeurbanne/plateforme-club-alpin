<?php

namespace App\Tests\Helper;

use App\Entity\TransportModeEnum;
use App\Helper\CarbonCostHelper;
use PHPUnit\Framework\TestCase;

class CarbonCostHelperTest extends TestCase
{
    private CarbonCostHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CarbonCostHelper(
            publicTrainRate: 10,
            publicCoachRate: 122,
            dedicatedCoachRate: 870,
            minivanRate: 327,
            bikeWalkRate: 0,
            thermicCarpoolingRate: 219,
            electricCarpoolingRate: 102,
            planeRate: 10000,
        );
    }

    public function testCalculateTrainMultipliesByPersonCount(): void
    {
        // Train: 100 km * 10 gCO2e/km * 5 persons = 5000
        $result = $this->helper->calculate(100, 5, 2, TransportModeEnum::PUBLIC_TRAIN);
        $this->assertEquals(5000.0, $result);
    }

    public function testCalculateCarpoolingMultipliesByVehicleCount(): void
    {
        // Thermic carpooling: 100 km * 219 gCO2e/km * 3 vehicles = 65700
        $result = $this->helper->calculate(100, 5, 3, TransportModeEnum::THERMIC_CARPOOLING);
        $this->assertEquals(65700.0, $result);
    }

    public function testCalculateBikeReturnsZero(): void
    {
        $result = $this->helper->calculate(100, 5, 1, TransportModeEnum::BIKE_OR_WALK);
        $this->assertEquals(0.0, $result);
    }

    public function testCalculateNullTransportModeReturnsNull(): void
    {
        $result = $this->helper->calculate(100, 5, 1, null);
        $this->assertNull($result);
    }

    public function testCalculateZeroKmReturnsZero(): void
    {
        $result = $this->helper->calculate(0, 5, 1, TransportModeEnum::PUBLIC_TRAIN);
        $this->assertEquals(0.0, $result);
    }

    public function testCalculateEnforcesMinimumOneForCounts(): void
    {
        // With 0 persons, max(1, 0) = 1 → 100 * 10 * 1 = 1000
        $result = $this->helper->calculate(100, 0, 0, TransportModeEnum::PUBLIC_TRAIN);
        $this->assertEquals(1000.0, $result);

        // With 0 vehicles on carpooling, max(1, 0) = 1 → 100 * 219 * 1 = 21900
        $result = $this->helper->calculate(100, 5, 0, TransportModeEnum::THERMIC_CARPOOLING);
        $this->assertEquals(21900.0, $result);
    }
}
