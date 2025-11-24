<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

class MonitoringController extends AbstractController
{
    #[Route(name: 'monitoring_http', path: '/monitoring/{code}', requirements: ['code' => '\d+'], methods: ['GET'])]
    public function httpAction(int $code)
    {
        if (200 == $code) {
            return new Response(200, 200);
        }

        throw new HttpException($code, sprintf('HTTP Exception (%d) thrown by monitoring system for test purpose.', $code));
    }

    #[Route(name: 'monitoring_log', path: '/monitoring/log', methods: ['GET'])]
    public function logAction(LoggerInterface $logger)
    {
        $logger->error('Test message');

        return new Response('log!', 200);
    }
}
