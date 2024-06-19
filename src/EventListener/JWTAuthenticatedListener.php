<?php

namespace App\EventListener;

use App\Security\AdminDetector;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTAuthenticatedListener implements EventSubscriberInterface
{

    /**
     * @param JWTAuthenticatedEvent $event
     *
     * @return void
     */
    public function onJWTAuthenticated(JWTAuthenticatedEvent $event)
    {
        $token = $event->getToken();
        $payload = $event->getPayload();

        if ($payload['is_admin'] ?? false) {
            $token->setAttribute('is_admin', $payload['is_admin']);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_AUTHENTICATED => 'onJWTAuthenticated',
        ];
    }
}
