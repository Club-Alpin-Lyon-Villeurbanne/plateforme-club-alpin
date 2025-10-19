<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class AuthenticationListener implements EventSubscriberInterface, ServiceSubscriberInterface
{
    private bool $setCookie = false;
    private bool $removeCookie = false;
    private ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            TokenStorageInterface::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
            LogoutEvent::class => 'onLogout',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $interactiveLoginEvent)
    {
        $this->setCookie = true;
    }

    public function onLogout(LogoutEvent $deauthenticatedEvent)
    {
        $this->removeCookie = true;
    }

    public function onResponse(ResponseEvent $responseEvent)
    {
        // Skip DB operations for non-successful responses (404, 500, etc.)
        if (!$responseEvent->getResponse()->isSuccessful()) {
            return;
        }

        if ($this->setCookie) {
            if ($token = $this->locator->get(TokenStorageInterface::class)->getToken()) {
                $user = $token->getUser();
                if ($user instanceof User) {
                    $token = bin2hex(random_bytes(16));
                    $user->setCookietoken($token);
                    $this->locator->get(EntityManagerInterface::class)->flush();

                    $responseEvent->getResponse()->headers->setCookie(Cookie::create('cafuser', sprintf('%d-%s', $user->getId(), $token)));
                }
            }
        }

        if ($this->removeCookie) {
            $responseEvent->getResponse()->headers->removeCookie('cafuser');
        }
    }
}
