<?php

namespace App\Controller;

use AdrienGras\PKCE\PKCEUtils;
use App\Repository\ConfigRepository;
use App\Service\HelloAssoService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoController extends AbstractController
{
    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $organizationSlug,
        protected string $baseUrl,
        protected ConfigRepository $configRepository,
        protected HttpClientInterface $httpClient,
        protected RouterInterface $router
    ) {
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/ha-mire', name: 'mire_ha', methods: ['GET'])]
    #[Template('hello-asso/mire.html.twig')]
    public function mire(HelloAssoService $helloAssoService): array
    {
        if (!$this->isGranted('HELLO_ASSO_MIRE')) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à effectuer cette action.');
        }

        $authUrl = '';

        // vérifier si on a un refresh token et l'utiliser plutôt que demander un access token
        $organizationRefreshToken = $helloAssoService->getRefreshToken('organization');

        if (!$organizationRefreshToken) {
            // se connecter en tant que "partenaire"
            $partnerAccessToken = $helloAssoService->login();

            // mettre à jour le domaine partenaire (les URLs de redirection doivent être dessus)
            $helloAssoService->updatePartnerDomain($partnerAccessToken);

            // générer les clés de vérification
            $codeVerifier = PKCEUtils::generateCodeVerifier(100);
            $state = $helloAssoService->generateState(50);

            // les enregistrer pour plus tard
            $this->configRepository->saveConfigValue('oauth_code_verifier', $codeVerifier);
            $this->configRepository->saveConfigValue('state', $state);

            // générer l'url d'autorisation + redirection vers la mire
            $authUrl = $helloAssoService->getAuthorizationUrl($codeVerifier, $state);
        }

        return [
            'authorization_url' => $authUrl,
            'organization_token' => $organizationRefreshToken,
        ];
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    #[Route('/ha-mire-callback', name: 'mire_ha_callback')]
    public function callback(HelloAssoService $helloAssoService, Request $request): RedirectResponse
    {
        $requestData = $request->query->all();
        if (\in_array('error', array_keys($requestData), true)) {
            $this->addFlash('error', 'Une erreur est survenue (' . $requestData['error_description'] . '). Merci de réessayer.');

            return $this->redirectToRoute('mire_ha');
        }

        // récupérer l'autorization_code dans la réponse
        $code = $requestData['code'];
        $state = $requestData['state'];

        // récupérer les clés de vérification sauvegardées
        $originalCodeVerifier = $this->configRepository->getConfigValue('oauth_code_verifier');
        $originalState = $this->configRepository->getConfigValue('state');

        // vérification de validité de la réponse
        if (!$code || !$originalCodeVerifier || $originalState !== $state) {
            throw new \Exception('Invalid OAuth callback');
        }

        // échanger cet autorization_code contre un access_token
        $helloAssoService->getAccessTokenFromAuthCode($code, $originalCodeVerifier);

        // supprimer les clés de vérification sauvegardées
        $this->configRepository->removeConfigValue('oauth_code_verifier');
        $this->configRepository->removeConfigValue('state');

        $this->addFlash('success', 'HelloAsso est désormais configuré.');

        return $this->redirectToRoute('legacy_root');
    }
}
