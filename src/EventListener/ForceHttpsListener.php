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
    private string $routerContextScheme;

    public function __construct(ContainerInterface $locator, string $routerContextScheme)
    {
        $this->locator = $locator;
        $this->routerContextScheme = $routerContextScheme;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ('https' !== $this->routerContextScheme) {
            return;
        }

        if ($event->getRequest()->isSecure()) {
            return;
        }

        $urlGenerator = $this->locator->get(UrlGeneratorInterface::class);
        $urlGenerator->getContext()->setScheme($this->routerContextScheme);

        $url = $this->locator->get(UrlGeneratorInterface::class)->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $event->setResponse(
            new RedirectResponse($url, 301)
        );
    }

    public static function getSubscribedServices(): array
    {
        return [
            UrlGeneratorInterface::class,
        ];
    }
}
