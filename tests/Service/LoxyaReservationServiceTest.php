<?php

namespace App\Tests\Service;

use App\Service\LoxyaReservationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LoxyaReservationServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private LoxyaReservationService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new LoxyaReservationService(
            'https://materiel.example.com',
            'static-jwt-token',
            $this->httpClient,
            $this->logger,
        );
    }

    public function testMarkReservationAsPaidCallsCorrectEndpoint(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'https://materiel.example.com/api/reservations/42',
                $this->callback(function (array $options) {
                    return 'Bearer static-jwt-token' === $options['headers']['Authorization']
                        && LoxyaReservationService::RESERVATION_PAID_COLOR === $options['json']['color']
                        && 'Paiement Helloasso n°HA-12345' === $options['json']['note'];
                })
            )
            ->willReturn($response);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Loxya reservation updated after payment', [
                'reservationId' => 42,
                'helloAssoPaymentId' => 'HA-12345',
            ]);

        $this->service->markReservationAsPaid(42, 'HA-12345');
    }

    public function testMarkReservationAsPaidThrowsOnError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getContent')->willReturn('Internal Server Error');

        $this->httpClient->method('request')->willReturn($response);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Loxya reservation update failed', $this->anything());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Loxya reservation update failed (HTTP 500)');

        $this->service->markReservationAsPaid(42, 'HA-12345');
    }
}
