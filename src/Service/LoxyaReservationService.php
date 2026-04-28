<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoxyaReservationService
{
    // Convention visuelle convenue avec Loxya pour signaler une réservation payée tant qu'il n'y a pas de flag dédié côté Loxya.
    public const string RESERVATION_PAID_COLOR = '#5dd0c2';

    public function __construct(
        private readonly string $loxyaBaseUrl,
        private readonly string $loxyaJwt,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * DOIT rester idempotent : appelé depuis le webhook de paiement qui peut être
     * rejoué (retry HelloAsso, race concurrente). Deux appels successifs avec le même
     * $helloAssoPaymentId doivent produire le même état final côté Loxya.
     */
    public function markReservationAsPaid(int $reservationId, string $helloAssoPaymentId): void
    {
        $url = rtrim($this->loxyaBaseUrl, '/') . '/api/reservations/' . $reservationId;
        $safePaymentId = substr(preg_replace('/[^A-Za-z0-9_-]/', '', $helloAssoPaymentId), 0, 64);

        $response = $this->httpClient->request('PUT', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->loxyaJwt,
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
            ],
            'json' => [
                'color' => self::RESERVATION_PAID_COLOR,
                'note' => sprintf('Paiement Helloasso n°%s', $safePaymentId),
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->error('Loxya reservation update failed', [
                'reservationId' => $reservationId,
                'statusCode' => $statusCode,
            ]);
            throw new \RuntimeException(sprintf('Loxya reservation update failed (HTTP %d)', $statusCode));
        }

        $this->logger->info('Loxya reservation updated after payment', [
            'reservationId' => $reservationId,
            'helloAssoPaymentId' => $helloAssoPaymentId,
        ]);
    }
}
