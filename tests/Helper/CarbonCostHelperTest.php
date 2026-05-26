<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Entity\TransportModeEnum;
use App\Helper\CarbonCost;
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
        );
    }

    public function testCalculateTrainMultipliesByPersonCount(): void
    {
        // Train: 100 km * 10 gCO2e/km * 5 persons = 5000 total, perPerson = 1000
        $result = $this->helper->calculate(100, 5, 2, TransportModeEnum::PUBLIC_TRAIN);
        $this->assertInstanceOf(CarbonCost::class, $result);
        $this->assertEquals(5000.0, $result->total);
        $this->assertEquals(1000.0, $result->perPerson);
    }

    public function testCalculateCarpoolingMultipliesByVehicleCount(): void
    {
        // Thermic carpooling: 100 km * 219 gCO2e/km * 3 vehicles = 65700 total ; perPerson = 65700/5 = 13140
        $result = $this->helper->calculate(100, 5, 3, TransportModeEnum::THERMIC_CARPOOLING);
        $this->assertEquals(65700.0, $result->total);
        $this->assertEquals(13140.0, $result->perPerson);
    }

    public function testCalculateMinivanReturnsPerPerson(): void
    {
        // Minibus: 100 km * 327 gCO2e/km * 1 vehicle = 32700 total ; perPerson = 32700/9 ≈ 3633.33
        $result = $this->helper->calculate(100, 9, 1, TransportModeEnum::MINIVAN);
        $this->assertEquals(32700.0, $result->total);
        $this->assertEquals(3633.33, $result->perPerson);
    }

    public function testCalculateDedicatedCoachReturnsPerPerson(): void
    {
        // Car affrété: 100 km * 870 gCO2e/km * 1 vehicle = 87000 total ; perPerson = 87000/50 = 1740
        $result = $this->helper->calculate(100, 50, 1, TransportModeEnum::DEDICATED_COACH);
        $this->assertEquals(87000.0, $result->total);
        $this->assertEquals(1740.0, $result->perPerson);
    }

    public function testCalculateBikeReturnsZero(): void
    {
        $result = $this->helper->calculate(100, 5, 1, TransportModeEnum::BIKE_OR_WALK);
        $this->assertEquals(0.0, $result->total);
        $this->assertEquals(0.0, $result->perPerson);
    }

    public function testCalculateNullTransportModeReturnsNull(): void
    {
        $result = $this->helper->calculate(100, 5, 1, null);
        $this->assertNull($result);
    }

    public function testCalculateObsoletePlaneModeReturnsNull(): void
    {
        // PLANE est conservé dans l'enum pour rétro-compat d'hydratation Doctrine
        // (sorties pré-migration), mais doit être inerte côté calcul carbone.
        $result = $this->helper->calculate(100, 5, 1, TransportModeEnum::PLANE);
        $this->assertNull($result);
    }

    public function testCalculateZeroKmReturnsNull(): void
    {
        // nbKm=0 = donnée manquante (OSRM en échec, coords absentes…), pas un
        // trajet valide à 0 km. On retourne null pour que le template masque le
        // bloc plutôt que d'afficher un trompeur « 0,0 kg CO₂e ».
        $this->assertNull($this->helper->calculate(0, 5, 1, TransportModeEnum::PUBLIC_TRAIN));
        $this->assertNull($this->helper->calculate(0, 5, 1, TransportModeEnum::BIKE_OR_WALK));
    }

    public function testCalculateEnforcesMinimumOneForCounts(): void
    {
        // With 0 persons, max(1, 0) = 1 → 100 * 10 * 1 = 1000 ; perPerson = 1000/1 = 1000
        $result = $this->helper->calculate(100, 0, 0, TransportModeEnum::PUBLIC_TRAIN);
        $this->assertEquals(1000.0, $result->total);
        $this->assertEquals(1000.0, $result->perPerson);

        // With 0 vehicles on carpooling, max(1, 0) = 1 → 100 * 219 * 1 = 21900 ; perPerson = 21900/5 = 4380
        $result = $this->helper->calculate(100, 5, 0, TransportModeEnum::THERMIC_CARPOOLING);
        $this->assertEquals(21900.0, $result->total);
        $this->assertEquals(4380.0, $result->perPerson);
    }
}
