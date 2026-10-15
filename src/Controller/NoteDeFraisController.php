<?php

namespace App\Controller;

use App\Entity\Evt;
use App\Entity\User;
use App\Repository\CommissionRepository;
use App\Repository\ExpenseReportRepository;
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
    // Le formulaire de signalement des autres pages du site.
    private const string FORMULAIRE_DE_SIGNALEMENT = 'https://forms.clickup.com/42653954/f/18np82-775/1BKP6TIKU0RIYXCRWE';

    public function __construct(
        #[Autowire(env: 'bool:DISPLAY_NOTES_DE_FRAIS')]
        private readonly bool $notesDeFraisActives,
        #[Autowire(param: 'expense_report')]
        private readonly array $configNotesDeFrais,
    ) {
    }

    #[Route(path: '/notes-de-frais', name: 'notes_de_frais_mes_notes', methods: ['GET'])]
    public function mesNotes(ControlesNoteHorsSortie $controles, Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal('apiBaseUrl', $baseUrl);

        // Ouverte à tous : les mails y renvoient, même après la perte d'un rôle.
        $utilisateur = $this->getUser();

        return $this->render('notes-de-frais/mes-notes.html.twig', [
            'donnees' => [
                'delaisEnvoiJours' => $this->configNotesDeFrais['horsSortie']['delaiEnvoiJours'],
                'peutDeposer' => $utilisateur instanceof User && $controles->peutDeposer($utilisateur),
            ],
        ]);
    }

    #[Route(path: '/notes-de-frais/nouvelle', name: 'notes_de_frais_nouvelle', methods: ['GET'])]
    public function nouvelle(Request $request, CommissionRepository $commissionRepository, ValideursNoteHorsSortie $valideurs, ControlesNoteHorsSortie $controles, ExpenseReportCalculator $calculator, Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $utilisateur = $this->getUser();
        $noteId = $request->query->getInt('id') ?: null;
        // Une note commencée reste à finir, même après la perte du rôle.
        if (null === $noteId && !($utilisateur instanceof User && $controles->peutDeposer($utilisateur))) {
            return $this->render('notes-de-frais/reservee.html.twig', ['message' => ControlesNoteHorsSortie::RESERVEES_A_L_ENCADREMENT]);
        }

        $visibles = iterator_to_array($commissionRepository->findVisible(), false);
        // Pour la phrase « Sera transmise à … » : l'utilisateur connecté ne valide pas sa propre note.
        $valideursParCommission = $utilisateur instanceof User ? $valideurs->pourLesCommissions($visibles, $utilisateur) : [];
        $commissionsDontIlEstResponsable = $utilisateur instanceof User ? $valideurs->commissionsValideesPar($utilisateur) : [];

        $commissions = [];
        foreach ($visibles as $commission) {
            $commissions[] = [
                'id' => $commission->getId(),
                'titre' => $commission->getTitle(),
                'valideurs' => ValideursNoteHorsSortie::exposer($valideursParCommission[$commission->getId()] ?? []),
                // Sans autre valideur, la page lui dit pourquoi sa note va directement à la trésorerie.
                'demandeurResponsable' => \in_array($commission->getId(), $commissionsDontIlEstResponsable, true),
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
                // « Sortie » au premier choix : la note se fait depuis la sortie, sous ces conditions.
                'sortie' => [
                    'delaiJours' => Evt::EXPENSE_REPORT_DEADLINE_DAYS,
                    'sortiesPassees' => $this->generateUrl('profil_sorties_prev'),
                    'signalement' => self::FORMULAIRE_DE_SIGNALEMENT,
                ],
                // Reprise d'un brouillon : l'application le charge par l'API.
                'noteId' => $noteId,
            ],
        ]);
    }

    #[Route(path: '/notes-de-frais/a-valider', name: 'notes_de_frais_a_valider', methods: ['GET'])]
    public function aValider(Request $request, Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal('apiBaseUrl', $baseUrl);

        return $this->render('notes-de-frais/a-valider.html.twig', [
            'donnees' => ['traitees' => $request->query->has('traitees')],
        ]);
    }

    // Cible du lien « Voir la note » des mails. L'API dit qui peut lire la note.
    #[Route(path: '/notes-de-frais/{id}', name: 'notes_de_frais_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(int $id, ExpenseReportRepository $notes, ValideursNoteHorsSortie $valideurs, Environment $twig, string $baseUrl): Response
    {
        $this->pageDisponible();

        $twig->getExtension(JavascriptGlobalsExtension::class)->registerGlobal('apiBaseUrl', $baseUrl);
        $demandeur = $notes->find($id)?->getUser();
        $utilisateur = $this->getUser();

        return $this->render('notes-de-frais/detail.html.twig', [
            'donnees' => [
                'noteId' => $id,
                // Le fil d'Ariane et le choix entre don et remboursement ne sont que pour lui.
                'estDemandeur' => $utilisateur instanceof User && $demandeur?->getId() === $utilisateur->getId(),
                // Le fil d'Ariane des autres suit leur rôle, pas la note : une note interdite ou inconnue n'y paraît pas.
                'estValideur' => $utilisateur instanceof User && [] !== $valideurs->commissionsValideesPar($utilisateur),
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
