<?php

namespace App\EventListener;

use App\Entity\CafUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\DeauthenticatedEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
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

    public static function getSubscribedServices()
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
            DeauthenticatedEvent::class => 'onLogout',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $interactiveLoginEvent)
    {
        $this->setCookie = true;
    }

    public function onLogout(DeauthenticatedEvent $deauthenticatedEvent)
    {
        $this->removeCookie = true;
    }

    public function onResponse(ResponseEvent $responseEvent)
    {
        if ($this->setCookie) {
            if ($token = $this->locator->get(TokenStorageInterface::class)->getToken()) {
                $user = $token->getUser();
                if ($user instanceof CafUser) {
                    $token = bin2hex(random_bytes(16));
                    $user->setCookietokenUser($token);
                    $this->locator->get(EntityManagerInterface::class)->flush();

                    $responseEvent->getResponse()->headers->setCookie(Cookie::create('cafuser', sprintf('%d-%s', $user->getIdUser(), $token)));
                }
            }
        }

        if ($this->removeCookie) {
            $responseEvent->getResponse()->headers->removeCookie('cafuser');
        }
    }
}
