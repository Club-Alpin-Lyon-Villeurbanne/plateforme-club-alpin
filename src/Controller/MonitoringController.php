<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class MonitoringController
{
    /**
     * @Route(
     *     name="monitoring_http",
     *     path="/monitoring/{code}",
     *     methods={"GET"}
     * )
     */
    public function httpAction($code)
    {
        if (200 == $code) {
            return new Response(200, 200);
        }

        throw new HttpException($code, sprintf('HTTP Exception (%d) thrown by monitoring system for test purpose.', $code));
    }
}
