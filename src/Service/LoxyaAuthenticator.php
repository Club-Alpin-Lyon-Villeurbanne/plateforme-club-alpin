<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoxyaAuthenticator
{
    private ?string $token = null;

    public function __construct(
        private readonly string $apiBaseUrl,
        private readonly string $apiUsername,
        private readonly string $apiPassword,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Retourne un JWT Loxya obtenu via /api/session. Le token est mémoïsé pour la
     * durée de la requête : création de compte et mise à jour de réservation
     * partagent le même login plutôt que d'en déclencher un chacun.
     */
    public function getToken(): string
    {
        return $this->token ??= $this->authenticate();
    }

    private function authenticate(): string
    {
        $url = rtrim($this->apiBaseUrl, '/') . '/api/session';

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'identifier' => $this->apiUsername,
                    'password' => $this->apiPassword,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if (Response::HTTP_OK !== $statusCode) {
                // Pas de body upstream dans le message : il peut contenir des infos sensibles.
                $this->logger->error('Loxya authentication failed', ['statusCode' => $statusCode]);
                throw new \RuntimeException(sprintf('Loxya authentication failed (HTTP %d)', $statusCode));
            }

            $token = $response->toArray()['token'] ?? null;
            if (!\is_string($token) || '' === $token) {
                throw new \RuntimeException('Loxya authentication response missing token');
            }

            return $token;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Loxya authentication error', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Loxya authentication failed', 0, $e);
        }
    }
}
