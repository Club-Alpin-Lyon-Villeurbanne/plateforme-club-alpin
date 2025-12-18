<?php

namespace App\Tests\Service;

use App\Service\ExpenseReportCalculator;
use PHPUnit\Framework\TestCase;

class ExpenseReportCalculatorTest extends TestCase
{
    private ExpenseReportCalculator $calculator;

    protected function setUp(): void
    {
        $config = [
            'nuiteeMaxRemboursable' => 60.0,
            'tauxKilometriqueVoiture' => 0.2,
            'tauxKilometriqueMinibus' => 0.15,
            'divisionPeage' => 3,
        ];
        $this->calculator = new ExpenseReportCalculator($config);
    }

    // ============== TRANSPORT TESTS ==============

    public function testCalculateTransportTotalPersonalVehicle(): void
    {
        $transport = [
            'type' => 'PERSONAL_VEHICLE',
            'distance' => 100,
            'tollFee' => 9,
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        // (100 * 0.2) + (9 / 3) = 20 + 3 = 23
        $this->assertEqualsWithDelta(23.0, $result, 0.01);
    }

    public function testCalculateTransportTotalPersonalVehicleWithZeroDistance(): void
    {
        $transport = [
            'type' => 'PERSONAL_VEHICLE',
            'distance' => 0,
            'tollFee' => 6,
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        // (0 * 0.2) + (6 / 3) = 2
        $this->assertEqualsWithDelta(2.0, $result, 0.01);
    }

    public function testCalculateTransportTotalClubMinibus(): void
    {
        $transport = [
            'type' => 'CLUB_MINIBUS',
            'distance' => 200,
            'fuelExpense' => 40,
            'tollFee' => 15,
            'passengerCount' => 5,
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        // ((200 * 0.15) + 40 + 15) / 5 = (30 + 40 + 15) / 5 = 85 / 5 = 17
        $this->assertEqualsWithDelta(17.0, $result, 0.01);
    }

    public function testCalculateTransportTotalClubMinibusWithZeroPassengers(): void
    {
        $transport = [
            'type' => 'CLUB_MINIBUS',
            'distance' => 200,
            'fuelExpense' => 40,
            'tollFee' => 15,
            'passengerCount' => 0,
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        // Should return 0 when passengerCount is 0
        $this->assertEqualsWithDelta(0.0, $result, 0.01);
    }

    public function testCalculateTransportTotalRentalMinibus(): void
    {
        $transport = [
            'type' => 'RENTAL_MINIBUS',
            'rentalPrice' => 300,
            'fuelExpense' => 50,
            'tollFee' => 20,
            'passengerCount' => 8,
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        // (300 + 50 + 20) / 8 = 370 / 8 = 46.25
        $this->assertEqualsWithDelta(46.25, $result, 0.01);
    }

    public function testCalculateTransportTotalRentalMinibusWithNegativePassengers(): void
    {
        $transport = [
            'type' => 'RENTAL_MINIBUS',
            'rentalPrice' => 300,
            'fuelExpense' => 50,
            'tollFee' => 20,
            'passengerCount' => -1,
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        // Should return 0 when passengerCount is negative
        $this->assertEqualsWithDelta(0.0, $result, 0.01);
    }

    public function testCalculateTransportTotalPublicTransport(): void
    {
        $transport = [
            'type' => 'PUBLIC_TRANSPORT',
            'ticketPrice' => 25.5,
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        $this->assertEqualsWithDelta(25.5, $result, 0.01);
    }

    public function testCalculateTransportTotalUnknownType(): void
    {
        $transport = [
            'type' => 'UNKNOWN_TYPE',
            'distance' => 100,
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        // Should return 0 for unknown types
        $this->assertEqualsWithDelta(0.0, $result, 0.01);
    }

    public function testCalculateTransportTotalEmptyArray(): void
    {
        $transport = [];

        $result = $this->calculator->calculateTransportTotal($transport);
        // Should return 0 when array is empty
        $this->assertEqualsWithDelta(0.0, $result, 0.01);
    }

    public function testCalculateTransportTotalMissingFields(): void
    {
        $transport = [
            'type' => 'PERSONAL_VEHICLE',
            // distance and tollFee are missing
        ];

        $result = $this->calculator->calculateTransportTotal($transport);
        // (0 * 0.2) + (0 / 3) = 0
        $this->assertEqualsWithDelta(0.0, $result, 0.01);
    }

    // ============== ACCOMMODATION TESTS ==============

    public function testCalculateAccommodationTotalsSingleAccommodation(): void
    {
        $accommodations = [
            ['price' => 50],
        ];

        $result = $this->calculator->calculateAccommodationTotals($accommodations);

        $this->assertEqualsWithDelta(50.0, $result['total'], 0.01);
        // Reimbursable is min(50, 60) = 50
        $this->assertEqualsWithDelta(50.0, $result['reimbursable'], 0.01);
    }

    public function testCalculateAccommodationTotalsExceedsMax(): void
    {
        $accommodations = [
            ['price' => 70],
            ['price' => 75],
        ];

        $result = $this->calculator->calculateAccommodationTotals($accommodations);

        $this->assertEqualsWithDelta(145.0, $result['total'], 0.01);
        // Reimbursable is min(70, 60) + min(75, 60) = 60 + 60 = 120
        $this->assertEqualsWithDelta(120.0, $result['reimbursable'], 0.01);
    }

    public function testCalculateAccommodationTotalsMultiple(): void
    {
        $accommodations = [
            ['price' => 55],
            ['price' => 50],
            ['price' => 45],
        ];

        $result = $this->calculator->calculateAccommodationTotals($accommodations);

        $this->assertEqualsWithDelta(150.0, $result['total'], 0.01);
        // All are below max, so reimbursable = total
        $this->assertEqualsWithDelta(150.0, $result['reimbursable'], 0.01);
    }

    public function testCalculateAccommodationTotalsEmpty(): void
    {
        $accommodations = [];

        $result = $this->calculator->calculateAccommodationTotals($accommodations);

        $this->assertEqualsWithDelta(0.0, $result['total'], 0.01);
        $this->assertEqualsWithDelta(0.0, $result['reimbursable'], 0.01);
    }

    public function testCalculateAccommodationTotalsMissingPrice(): void
    {
        $accommodations = [
            ['price' => 50],
            [], // Missing price
            ['price' => 40],
        ];

        $result = $this->calculator->calculateAccommodationTotals($accommodations);

        $this->assertEqualsWithDelta(90.0, $result['total'], 0.01);
        $this->assertEqualsWithDelta(90.0, $result['reimbursable'], 0.01);
    }

    // ============== OTHERS TESTS ==============

    public function testCalculateOthersTotalSingleItem(): void
    {
        $others = [
            ['price' => 25],
        ];

        $result = $this->calculator->calculateOthersTotal($others);
        $this->assertEqualsWithDelta(25.0, $result, 0.01);
    }

    public function testCalculateOthersTotalMultiple(): void
    {
        $others = [
            ['price' => 10],
            ['price' => 15.5],
            ['price' => 20],
        ];

        $result = $this->calculator->calculateOthersTotal($others);
        $this->assertEqualsWithDelta(45.5, $result, 0.01);
    }

    public function testCalculateOthersTotalEmpty(): void
    {
        $others = [];

        $result = $this->calculator->calculateOthersTotal($others);
        $this->assertEqualsWithDelta(0.0, $result, 0.01);
    }

    public function testCalculateOthersTotalMissingPrice(): void
    {
        $others = [
            ['price' => 10],
            [], // Missing price
            ['price' => 20],
        ];

        $result = $this->calculator->calculateOthersTotal($others);
        $this->assertEqualsWithDelta(30.0, $result, 0.01);
    }

    // ============== TOTAL TESTS ==============

    public function testCalculateTotalComplete(): void
    {
        $details = [
            'transport' => [
                'type' => 'PERSONAL_VEHICLE',
                'distance' => 100,
                'tollFee' => 9,
            ],
            'accommodations' => [
                ['price' => 55],
                ['price' => 65],
            ],
            'others' => [
                ['price' => 10],
            ],
        ];

        $result = $this->calculator->calculateTotal($details);

        // transport: (100 * 0.2) + (9 / 3) = 20 + 3 = 23
        // accommodations total: 55 + 65 = 120, reimbursable: min(55,60) + min(65,60) = 55 + 60 = 115
        // others: 10
        // total: 23 + 120 + 10 = 153
        // reimbursable: 23 + 115 + 10 = 148

        $this->assertEqualsWithDelta(153.0, $result['total'], 0.01);
        $this->assertEqualsWithDelta(148.0, $result['reimbursable'], 0.01);
    }

    public function testCalculateTotalEmpty(): void
    {
        $details = [];

        $result = $this->calculator->calculateTotal($details);

        $this->assertEqualsWithDelta(0.0, $result['total'], 0.01);
        $this->assertEqualsWithDelta(0.0, $result['reimbursable'], 0.01);
    }

    public function testCalculateTotalPartial(): void
    {
        $details = [
            'transport' => [
                'type' => 'PUBLIC_TRANSPORT',
                'ticketPrice' => 20,
            ],
            'accommodations' => [
                ['price' => 50],
            ],
        ];

        $result = $this->calculator->calculateTotal($details);

        // transport: 20
        // accommodations: total 50, reimbursable 50 (below max)
        // others: 0
        // total: 20 + 50 + 0 = 70
        // reimbursable: 20 + 50 + 0 = 70

        $this->assertEqualsWithDelta(70.0, $result['total'], 0.01);
        $this->assertEqualsWithDelta(70.0, $result['reimbursable'], 0.01);
    }

    // ============== FORMAT EUROS TESTS ==============

    public function testFormatEuros(): void
    {
        $result = $this->calculator->formatEuros(123.45);
        // Should format in French locale
        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString('€', $result);
    }

    public function testFormatEurosZero(): void
    {
        $result = $this->calculator->formatEuros(0);
        $this->assertStringContainsString('0', $result);
        $this->assertStringContainsString('€', $result);
    }

    // ============== GETTER TESTS ==============

    public function testGetTauxKilometriqueVoiture(): void
    {
        $this->assertEqualsWithDelta(0.2, $this->calculator->getTauxKilometriqueVoiture(), 0.01);
    }

    public function testGetTauxKilometriqueMinibus(): void
    {
        $this->assertEqualsWithDelta(0.15, $this->calculator->getTauxKilometriqueMinibus(), 0.01);
    }

    // ============== CONFIG TESTS ==============

    public function testConstructorWithDefaultValues(): void
    {
        $config = [];
        $calculator = new ExpenseReportCalculator($config);

        $this->assertEqualsWithDelta(0.0, $calculator->getTauxKilometriqueVoiture(), 0.01);
        $this->assertEqualsWithDelta(0.0, $calculator->getTauxKilometriqueMinibus(), 0.01);
    }

    public function testConstructorWithPartialConfig(): void
    {
        $config = [
            'tauxKilometriqueVoiture' => 0.25,
        ];
        $calculator = new ExpenseReportCalculator($config);

        $this->assertEqualsWithDelta(0.25, $calculator->getTauxKilometriqueVoiture(), 0.01);
        $this->assertEqualsWithDelta(0.0, $calculator->getTauxKilometriqueMinibus(), 0.01);
    }
}
