<?php
// api/src/State/BlogPostProvider.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class CurrentUserProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?User
    {
        return $this->security->getUser();
    }
}