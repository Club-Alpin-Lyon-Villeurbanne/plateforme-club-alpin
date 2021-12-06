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
    private string $routerContextHost;

    public function __construct(ContainerInterface $locator, string $routerContextScheme, string $routerContextHost)
    {
        $this->locator = $locator;
        $this->routerContextScheme = $routerContextScheme;
        $this->routerContextHost = $routerContextHost;
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

        if ($event->getRequest()->isSecure() && $event->getRequest()->getHttpHost() === $this->routerContextHost) {
            return;
        }

        $urlGenerator = $this->locator->get(UrlGeneratorInterface::class);
        $urlGenerator->getContext()->setScheme($this->routerContextScheme);
        $urlGenerator->getContext()->setHost($this->routerContextHost);

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
