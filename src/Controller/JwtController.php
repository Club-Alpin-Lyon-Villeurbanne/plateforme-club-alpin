<?php

namespace App\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class JwtController extends AbstractController
{
    #[Route(name: 'auth-jwt', path: '/jwt', methods: ['POST'])]
    public function indexAction(JWTTokenManagerInterface $JWTTokenManager)
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'You have been disconnected.',
                'errors' => null,
                'error_code' => 'E_USER_DISCONNECTED',
            ], Response::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => 'Bearer']);
        }

        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse([
            'id' => $user->getId(),
            'token' => $JWTTokenManager->create($user),
        ]);
    }
}
