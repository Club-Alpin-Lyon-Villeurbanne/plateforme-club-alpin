<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
readonly class LoginSuccessListener
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        if (method_exists($user, 'setLastLoginDate')) {
            $user->setLastLoginDate(new \DateTimeImmutable());
            $this->entityManager->flush();
        }
    }
}
