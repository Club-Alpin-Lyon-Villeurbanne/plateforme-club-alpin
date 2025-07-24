<?php

namespace App\Controller;

use AdrienGras\PKCE\PKCEUtils;
use App\Service\OAuthPkceClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HelloAssoController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route(path: '/mire', name: 'mire_ha', methods: ['GET', 'POST'])]
    public function mire(OAuthPkceClient $client, SessionInterface $session)
    {
        $codeVerifier = PKCEUtils::generateCodeVerifier();
        $authUrl = $client->getAuthorizationUrl($codeVerifier);

        $session->set('oauth_code_verifier', $codeVerifier);

        return $this->redirect($authUrl);
    }

    #[Route('/mire-callback', name: 'mire_ha_callback')]
    public function callback(Request $request, SessionInterface $session)
    {
        $code = $request->query->get('code');
        $codeVerifier = $session->get('oauth_code_verifier');

        if (!$code || !$codeVerifier || $codeVerifier !== $code) {
            throw new \Exception('Invalid OAuth callback');
        }
        
        // récuperer l'autorization_code dans la réponse

        return $this->redirect('/');
    }
}
