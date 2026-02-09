<?php

namespace App\Helper;

use App\Entity\Evt;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DistanceHelper
{
    protected const string API_ROUTE = 'http://router.project-osrm.org/route/v1/driving/{origin};{dest}?overview=false';

    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger,
    ) {
    }

    public function calculate(Evt $event): float
    {
        $distance = 0;
        $start = $event->getLong() . ',' . $event->getLat();
        $end = $event->getStartLong() . ',' . $event->getStartLat();

        $url = str_replace('{origin}', $start, self::API_ROUTE);
        $url = str_replace('{dest}', $end, $url);

        try {
            $response = $this->httpClient->request(
                'GET',
                $url,
            );
            $data = $response->toArray();
            $distance = $data['routes'][0]['distance'] / 1000;          // distance est en m dans la réponse
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $distance * 2;
    }
}
