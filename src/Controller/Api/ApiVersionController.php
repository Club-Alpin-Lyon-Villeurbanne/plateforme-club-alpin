<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class ApiVersionController extends AbstractController
{
    #[Route(path: '/api/version', name: 'api_version', methods: ['GET'])]
    public function getVersion(): Response
    {
        $version = $this->getParameter('api_version');

        return $this->json(['version' => $version]);
    }
}
