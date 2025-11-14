<?php

namespace App\EventSubscriber;

use App\Security\Voter\UserLoginVoter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

readonly class CheckUserStatusSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', -10],
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        if (!$this->authorizationChecker->isGranted(UserLoginVoter::LOGIN, $user)) {
            throw new CustomUserMessageAuthenticationException('Votre compte est supprimé. Vous ne pouvez pas vous connecter.');
        }
    }
}
