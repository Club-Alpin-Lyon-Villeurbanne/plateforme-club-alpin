<?php

namespace App\Controller;

use App\Repository\CommissionRepository;
use App\Twig\JavascriptGlobalsExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Pages des notes de frais hors sortie. Elles montent l'application Vue `notes-de-frais`, qui
 * lit et écrit tout par l'API.
 */
class NoteDeFraisController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'bool:DISPLAY_NOTES_DE_FRAIS')]
        private readonly bool $notesDeFraisActives,
        #[Autowire(param: 'expense_report')]
        private readonly array $configNotesDeFrais,
    ) {
    }

    #[Route(path: '/notes-de-frais/nouvelle', name: 'notes_de_frais_nouvelle', methods: ['GET'])]
    public function nouvelle(Request $request, CommissionRepository $commissionRepository, Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $commissions = [];
        foreach ($commissionRepository->findVisible() as $commission) {
            $commissions[] = ['id' => $commission->getId(), 'titre' => $commission->getTitle()];
        }

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal('apiBaseUrl', $baseUrl);

        return $this->render('notes-de-frais/nouvelle.html.twig', [
            'donnees' => [
                'commissions' => $commissions,
                'delaisEnvoiJours' => $this->configNotesDeFrais['horsSortie']['delaiEnvoiJours'],
                // Reprise d'un brouillon : l'application le charge par l'API.
                'noteId' => $request->query->getInt('id') ?: null,
            ],
        ]);
    }

    private function pageDisponible(): void
    {
        // Les clubs qui n'utilisent pas les notes de frais ne doivent pas voir que la page existe.
        if (!$this->notesDeFraisActives) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('ROLE_USER');
    }
}
