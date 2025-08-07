<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class ApiConfigController extends AbstractController
{
    #[Route(path: '/api/config', name: 'api_config', methods: ['GET'])]
    public function getConfig(): Response
    {
        $version = $this->getParameter('api_version');
        $minimumAppVersion = $this->getParameter('app_minimum_version');

        return $this->json(['version' => $version, 'appVersion' => $minimumAppVersion]);
    }
}
