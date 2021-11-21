<?php

namespace App\EventListener;

use Monolog\Handler\FingersCrossedHandler;
use Monolog\Logger;
use Profiler\Bridge\Monolog\Handler\SentryHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class SymfonyLogListener implements EventSubscriberInterface, ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'flush',
            ConsoleEvents::TERMINATE => 'flush',
        ];
    }

    public function flush(): void
    {
        $this->locator->get(SentryHandler::class)->flush();
        $logger = $this->locator->get(LoggerInterface::class);

        if (!$logger instanceof Logger) {
            return;
        }

        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FingersCrossedHandler) {
                $handler->clear();
            }
        }

        $logger->close();
    }

    public static function getSubscribedServices(): array
    {
        return [
            LoggerInterface::class,
            SentryHandler::class,
        ];
    }
}
