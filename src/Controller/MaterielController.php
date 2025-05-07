<?php

namespace App\Controller;

use App\Service\MaterielApiService;
use App\Service\MaterielEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MaterielController extends AbstractController
{
    public function __construct(
        private readonly MaterielApiService $materielApiService,
        private readonly MaterielEmailService $materielEmailService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route(path: '/materiel', name: 'materiel')]
    #[IsGranted('ROLE_USER')]
    #[Template('materiel/index.html.twig')]
    public function index(): array
    {
        return [
            'user' => $this->getUser(),
        ];
    }

    #[Route(path: '/materiel/create-account', name: 'materiel_create_account', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createAccount(Request $request): Response
    {
        $user = $this->getUser();
        $this->logger->info('Début de la création de compte pour l\'utilisateur', [
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
        ]);

        // Vérifier si la requête attend une réponse JSON (AJAX)
        $isAjax = 'XMLHttpRequest' === $request->headers->get('X-Requested-With');

        try {
            // Check if user already exists
            $this->logger->info('Vérification de l\'existence de l\'utilisateur');
            if ($this->materielApiService->userExists($user)) {
                $this->logger->warning('L\'utilisateur a déjà un compte sur la plateforme de réservation de matériel');

                if ($isAjax) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Vous avez déjà un compte sur la plateforme de réservation de matériel.',
                    ]);
                }

                $this->addFlash('error', 'Vous avez déjà un compte sur la plateforme de réservation de matériel.');

                return $this->redirectToRoute('materiel');
            }

            // Create user account
            $this->logger->info('Création du compte utilisateur');
            $credentials = $this->materielApiService->createUser($user);
            $this->logger->info('Compte utilisateur créé avec succès', [
                'pseudo' => $credentials['pseudo'],
            ]);

            // Send email with credentials
            $this->logger->info('Envoi de l\'email avec les identifiants');
            $this->materielEmailService->sendAccountCreationEmail(
                $user->getEmail(),
                $user->getFirstname(),
                $user->getLastname(),
                $credentials
            );
            $this->logger->info('Email envoyé avec succès');

            if ($isAjax) {
                return $this->json([
                    'success' => true,
                    'message' => 'Votre compte a été créé avec succès. Vous allez recevoir un email avec vos identifiants de connexion.',
                ]);
            }

            $this->addFlash('success', 'Votre compte a été créé avec succès. Vous allez recevoir un email avec vos identifiants de connexion.');

            return $this->redirectToRoute('materiel');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du compte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la création de votre compte. Veuillez réessayer plus tard.',
                ]);
            }

            $this->addFlash('error', 'Une erreur est survenue lors de la création de votre compte. Veuillez réessayer plus tard.');

            return $this->redirectToRoute('materiel');
        }
    }
}
