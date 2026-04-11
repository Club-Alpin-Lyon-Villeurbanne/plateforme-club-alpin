<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Evt;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DistanceHelper
{
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger,
        protected readonly string $osrmApiUrl = 'http://router.project-osrm.org/route/v1/driving/',
        protected readonly int $timeout = 5,
    ) {
    }

    /**
     * Calculate round-trip distance in km between the RDV point and the departure point.
     *
     * @param Evt $event the event with RDV coordinates (lat/long) and departure coordinates (latDepart/longDepart)
     *
     * @return float round-trip distance in km, 0 on failure
     */
    public function calculate(Evt $event): float
    {
        // Coordonnées non renseignées (valeur par défaut 0,0) → pas d'appel OSRM
        if (0.0 === (float) $event->getLatDepart() && 0.0 === (float) $event->getLongDepart()) {
            return 0;
        }
        if (0.0 === (float) $event->getLat() && 0.0 === (float) $event->getLong()) {
            return 0;
        }

        $distance = 0;
        // OSRM expects "longitude,latitude" format
        $rdvCoords = $event->getLong() . ',' . $event->getLat();
        $departureCoords = $event->getLongDepart() . ',' . $event->getLatDepart();

        $url = $this->osrmApiUrl . $rdvCoords . ';' . $departureCoords . '?overview=false';

        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => $this->timeout,
            ]);
            $data = $response->toArray();

            if (isset($data['routes'][0]['distance'])) {
                $distance = $data['routes'][0]['distance'] / 1000; // distance is in meters in the response
            } else {
                $this->logger->warning('OSRM returned no route', [
                    'url' => $url,
                    'response' => $data,
                ]);
            }
        } catch (\Exception $exception) {
            $this->logger->error('OSRM distance calculation failed: ' . $exception->getMessage());
        }

        return $distance * 2;
    }
}
