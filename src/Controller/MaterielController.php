<?php

namespace App\Controller;

use App\Service\MaterielApiService;
use App\Service\MaterielEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MaterielController extends AbstractController
{
    public function __construct(
        private readonly MaterielApiService $materielApiService,
        private readonly MaterielEmailService $materielEmailService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/materiel', name: 'materiel_index')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('materiel/index.html.twig', [
            'user' => $this->getUser(),
            'materiel_platform_url' => $this->getParameter('materiel_platform_url'),
        ]);
    }

    #[Route('/materiel/create-account', name: 'materiel_create_account', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createAccount(): Response
    {
        $user = $this->getUser();

        $this->logger->info('Début de la création de compte pour l\'utilisateur', [
            'email' => $user->getEmail(),
            'firstname' => ucfirst($user->getFirstname()),
            'lastname' => strtoupper($user->getLastname()),
        ]);

        // Vérification existence sur Loxya
        if ($this->materielApiService->userExistsOnLoxya($user)) {
            $this->logger->info('Utilisateur déjà existant sur Loxya', [
                'email' => $user->getEmail(),
            ]);
            $this->addFlash('error', 'Une erreur est survenue lors de la création de votre compte. Merci de remplir le formulaire <a href="https://forms.clickup.com/42653954/f/18np82-775/1BKP6TIKU0RIYXCRWE">https://forms.clickup.com/42653954/f/18np82-775/1BKP6TIKU0RIYXCRWE</a> en renseignant le code d\'erreur 409.');

            return $this->redirectToRoute('materiel_index');
        }

        try {
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
                ucfirst($user->getFirstname()),
                strtoupper($user->getLastname()),
                $credentials
            );
            $this->logger->info('Email envoyé avec succès');

            $this->addFlash('success', 'Votre compte a été créé avec succès. Vous allez recevoir un email avec vos identifiants de connexion.');

            return $this->redirectToRoute('materiel_index');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du compte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash('error', 'Une erreur est survenue lors de la création de votre compte. Merci de remplir le formulaire <a href="https://forms.clickup.com/42653954/f/18np82-775/1BKP6TIKU0RIYXCRWE">https://forms.clickup.com/42653954/f/18np82-775/1BKP6TIKU0RIYXCRWE</a> en renseignant le code d\'erreur 400.');

            return $this->redirectToRoute('materiel_index');
        }
    }
}
