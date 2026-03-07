<?php

namespace App\Tests\Helper;

use App\Entity\Evt;
use App\Helper\DistanceHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class DistanceHelperTest extends TestCase
{
    private function createEvent(float $lat = 45.76, float $long = 4.83, float $latDepart = 45.19, float $longDepart = 5.72): Evt
    {
        $event = $this->createMock(Evt::class);
        $event->method('getLat')->willReturn($lat);
        $event->method('getLong')->willReturn($long);
        $event->method('getLatDepart')->willReturn($latDepart);
        $event->method('getLongDepart')->willReturn($longDepart);

        return $event;
    }

    public function testCalculateReturnsRoundTripDistanceInKm(): void
    {
        $responseBody = json_encode([
            'routes' => [
                ['distance' => 50000], // 50 km one-way
            ],
        ]);
        $mockClient = new MockHttpClient([new MockResponse($responseBody)]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $helper = new DistanceHelper($mockClient, $logger);
        $result = $helper->calculate($this->createEvent());

        // 50000m / 1000 = 50km * 2 (round-trip) = 100km
        $this->assertEquals(100.0, $result);
    }

    public function testCalculateReturnsZeroOnEmptyRoutes(): void
    {
        $responseBody = json_encode(['routes' => []]);
        $mockClient = new MockHttpClient([new MockResponse($responseBody)]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $helper = new DistanceHelper($mockClient, $logger);
        $result = $helper->calculate($this->createEvent());

        $this->assertEquals(0.0, $result);
    }

    public function testCalculateReturnsZeroOnHttpException(): void
    {
        $mockClient = new MockHttpClient([new MockResponse('', ['http_code' => 500])]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $helper = new DistanceHelper($mockClient, $logger);
        $result = $helper->calculate($this->createEvent());

        $this->assertEquals(0.0, $result);
    }

    public function testCalculateLogsErrorOnFailure(): void
    {
        $mockClient = new MockHttpClient([new MockResponse('', ['http_code' => 500])]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')
            ->with($this->stringContains('OSRM distance calculation failed'));

        $helper = new DistanceHelper($mockClient, $logger);
        $helper->calculate($this->createEvent());
    }

    public function testCalculateReturnsZeroOnZeroDepartureCoords(): void
    {
        // Coordonnées de départ à 0,0 (valeur par défaut) → pas d'appel OSRM
        $mockClient = new MockHttpClient([]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');
        $logger->expects($this->never())->method('warning');

        $helper = new DistanceHelper($mockClient, $logger);
        $result = $helper->calculate($this->createEvent(latDepart: 0.0, longDepart: 0.0));

        $this->assertEquals(0.0, $result);
    }

    public function testCalculateReturnsZeroOnZeroRdvCoords(): void
    {
        // Coordonnées RDV à 0,0 → pas d'appel OSRM
        $mockClient = new MockHttpClient([]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $helper = new DistanceHelper($mockClient, $logger);
        $result = $helper->calculate($this->createEvent(lat: 0.0, long: 0.0));

        $this->assertEquals(0.0, $result);
    }
}
