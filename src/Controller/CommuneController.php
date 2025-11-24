<?php

namespace App\Controller;

use App\Entity\Commune;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CommuneController extends AbstractController
{
    #[Route('/commune/autocompletion', name: 'autocompletion_commune')]
    public function autocomplete(
        Request $request,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $requestText = $data['query'] ?? '';

        return new JsonResponse($doctrine->getRepository(Commune::class)->search($requestText));
    }
}
