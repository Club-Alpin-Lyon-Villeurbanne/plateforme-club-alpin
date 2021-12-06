<?php

namespace App\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class ForceHttpsListener implements EventSubscriberInterface, ServiceSubscriberInterface
{
    private ContainerInterface $locator;
    private bool $forceHttps;

    public function __construct(ContainerInterface $locator, bool $forceHttps)
    {
        $this->locator = $locator;
        $this->forceHttps = $forceHttps;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 9999],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$this->forceHttps) {
            return;
        }

        if ($event->getRequest()->isSecure()) {
            return;
        }

        $event->setResponse(
            new RedirectResponse($this->locator->get(UrlGeneratorInterface::class)->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL), 301)
        );
    }

    public static function getSubscribedServices(): array
    {
        return [
            UrlGeneratorInterface::class,
        ];
    }
}
