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
    private string $baseUrl;
    private string $appEnv;
    private string $deployedOnCleverCloud;

    public function __construct(ContainerInterface $locator, string $baseUrl, string $appName, string $appEnv)
    {
        $this->locator = $locator;
        $this->baseUrl = $baseUrl;
        $this->appEnv = $appEnv;
        $this->deployedOnCleverCloud = !empty($appName);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        // If deployed on CleverCloud we want to ignore this listener as Clevercloud
        // already does that
        if ($this->deployedOnCleverCloud) {
            return;
        }

        if ('dev' === $this->appEnv) {
            return;
        }

        if ($event->getRequest()->isSecure() && $event->getRequest()->getHttpHost() === parse_url($this->baseUrl, \PHP_URL_SCHEME)) {
            return;
        }

        $urlGenerator = $this->locator->get(UrlGeneratorInterface::class);
        $urlGenerator->getContext()->setBaseUrl($this->baseUrl);

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
