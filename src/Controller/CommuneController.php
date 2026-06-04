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
    #[Route('/commune/autocompletion', name: 'autocompletion_commune', methods: ['POST'])]
    public function autocomplete(
        Request $request,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $requestText = \is_array($data) ? trim((string) ($data['query'] ?? '')) : '';

        // une requête vide ferait un LIKE '%' renvoyant tout le référentiel : on court-circuite
        if ('' === $requestText) {
            return new JsonResponse([]);
        }

        // Seul le libellé canonique est exposé : les coordonnées sont dérivées côté
        // serveur à la soumission (cf. EventType), le client n'en a plus besoin.
        $suggestions = array_map(
            static fn (array $commune): array => [
                'label' => Commune::buildLabel($commune['codePostal'], $commune['nomCommune'], $commune['ligne5'] ?? null),
            ],
            $doctrine->getRepository(Commune::class)->search($requestText)
        );

        return new JsonResponse($suggestions);
    }
}
