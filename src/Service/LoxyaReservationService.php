<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoxyaReservationService
{
    public function __construct(
        private readonly string $loxyaBaseUrl,
        private readonly string $loxyaJwt,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function markReservationAsPaid(int $reservationId, string $helloAssoPaymentId): void
    {
        $url = rtrim($this->loxyaBaseUrl, '/') . '/api/reservations/' . $reservationId;

        $response = $this->httpClient->request('PUT', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->loxyaJwt,
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
            ],
            'json' => [
                'color' => '#5dd0c2',
                'note' => sprintf('Paiement Helloasso n°%s', $helloAssoPaymentId),
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->error('Loxya reservation update failed', [
                'reservationId' => $reservationId,
                'statusCode' => $statusCode,
                'response' => $response->getContent(false),
            ]);
            throw new \RuntimeException('Failed to update Loxya reservation ' . $reservationId);
        }

        $this->logger->info('Loxya reservation updated after payment', [
            'reservationId' => $reservationId,
            'helloAssoPaymentId' => $helloAssoPaymentId,
        ]);
    }
}
