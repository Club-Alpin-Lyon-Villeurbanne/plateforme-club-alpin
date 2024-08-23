<?php

namespace App\EventListener;

use App\Security\AdminDetector;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTCreatedListener implements EventSubscriberInterface
{
    private $adminDetector;

    public function __construct(AdminDetector $adminDetector)
    {
        $this->adminDetector = $adminDetector;
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $payload = $event->getData();
        $user = $event->getUser();
        $isAdmin = $this->adminDetector->isAdmin();
        if ($isAdmin) {
            $payload['is_admin'] = $isAdmin;
        }
        $payload = array_merge($payload, [
            'id' => $user->getId(),
            'nickname' => $user->getNickname(),
            'email' => $user->getEmail(),
        ]);

        $event->setData($payload);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJWTCreated',
        ];
    }
}
