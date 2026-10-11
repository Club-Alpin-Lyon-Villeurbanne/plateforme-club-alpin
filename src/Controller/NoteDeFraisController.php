<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CommissionRepository;
use App\Security\Voter\ExpenseReportVoter;
use App\Service\ExpenseReportCalculator;
use App\Service\NoteDeFrais\ControlesNoteHorsSortie;
use App\Service\NoteDeFrais\ValideursNoteHorsSortie;
use App\Twig\JavascriptGlobalsExtension;
use App\Utils\Enums\ExpenseReportTypeEnum;
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

    #[Route(path: '/notes-de-frais', name: 'notes_de_frais_mes_notes', methods: ['GET'])]
    public function mesNotes(Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal('apiBaseUrl', $baseUrl);

        return $this->render('notes-de-frais/mes-notes.html.twig', [
            'donnees' => ['delaisEnvoiJours' => $this->configNotesDeFrais['horsSortie']['delaiEnvoiJours']],
        ]);
    }

    #[Route(path: '/notes-de-frais/nouvelle', name: 'notes_de_frais_nouvelle', methods: ['GET'])]
    public function nouvelle(Request $request, CommissionRepository $commissionRepository, ValideursNoteHorsSortie $valideurs, ControlesNoteHorsSortie $controles, ExpenseReportCalculator $calculator, Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $visibles = iterator_to_array($commissionRepository->findVisible(), false);
        $utilisateur = $this->getUser();
        // Pour la phrase « Validée par … » : l'utilisateur connecté ne valide pas sa propre note.
        $valideursParCommission = $utilisateur instanceof User ? $valideurs->pourLesCommissions($visibles, $utilisateur) : [];

        $commissions = [];
        foreach ($visibles as $commission) {
            $commissions[] = [
                'id' => $commission->getId(),
                'titre' => $commission->getTitle(),
                'valideurs' => ValideursNoteHorsSortie::exposer($valideursParCommission[$commission->getId()] ?? []),
                // La page ne propose une formation que là où l'API l'accepterait.
                'formationPossible' => $utilisateur instanceof User && $controles->peutDemanderUneFormation($utilisateur, $commission),
            ];
        }

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal('apiBaseUrl', $baseUrl);

        return $this->render('notes-de-frais/nouvelle.html.twig', [
            'donnees' => [
                'commissions' => $commissions,
                'delaisEnvoiJours' => $this->configNotesDeFrais['horsSortie']['delaiEnvoiJours'],
                // Pour afficher le calcul d'une ligne de voiture pendant la saisie ; l'API fait foi.
                'formation' => [
                    'tauxKilometrique' => $calculator->tauxKilometriqueEnVigueur(),
                    'natures' => ExpenseReportTypeEnum::FORMATION->natures(),
                ],
                // Reprise d'un brouillon : l'application le charge par l'API.
                'noteId' => $request->query->getInt('id') ?: null,
            ],
        ]);
    }

    #[Route(path: '/notes-de-frais/a-valider', name: 'notes_de_frais_a_valider', methods: ['GET'])]
    public function aValider(Request $request, Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal('apiBaseUrl', $baseUrl);

        return $this->render('notes-de-frais/a-valider.html.twig', [
            'donnees' => [
                'traitees' => $request->query->has('traitees'),
                'gestionnaire' => $this->isGranted(ExpenseReportVoter::MANAGE_EXPENSE_REPORTS),
            ],
        ]);
    }

    // Cible du lien « Voir la demande » des mails. L'API dit qui peut lire la note.
    #[Route(path: '/notes-de-frais/{id}', name: 'notes_de_frais_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(int $id, Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal('apiBaseUrl', $baseUrl);

        $utilisateur = $this->getUser();

        return $this->render('notes-de-frais/detail.html.twig', [
            'donnees' => [
                'noteId' => $id,
                // Pour expliquer à un gestionnaire pourquoi il ne peut pas valider sa propre note.
                'utilisateurId' => $utilisateur instanceof User ? $utilisateur->getId() : null,
                'gestionnaire' => $this->isGranted(ExpenseReportVoter::MANAGE_EXPENSE_REPORTS),
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
