<?php

namespace App\Bridge\Monolog\Processor;

use Monolog\Processor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This class adds user information to the Monolog records when user is connected.
 */
class UserProcessor implements ProcessorInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Adds user information to a record.
     */
    public function __invoke(array $record): array
    {
        if (!$this->tokenStorage->getToken()) {
            return $record;
        }

        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user) {
            return $record;
        }

        if (method_exists($user, 'getUuid')) {
            $record['uuid'] = $user->getUuid();
        }

        if (method_exists($user, 'getProviderUsername')) {
            $record['username'] = $user->getProviderUsername();
        } elseif (method_exists($user, 'getUserIdentifier')) {
            $record['username'] = $user->getUserIdentifier();
        } elseif (method_exists($user, 'getUsername')) {
            $record['username'] = $user->getUsername();
        }

        return $record;
    }
}
