<?php

namespace App\Twig;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class JwtExtension extends AbstractExtension
{
    private $jwtManager;
    private $security;

    public function __construct(JWTTokenManagerInterface $jwtManager, Security $security)
    {
        $this->jwtManager = $jwtManager;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('jwt_token', [$this, 'generateJwtToken']),
        ];
    }

    public function generateJwtToken(): ?string
    {
        $user = $this->security->getUser();

        if ($user) {
            return $this->jwtManager->create($user);
        }

        return null;
    }
}
