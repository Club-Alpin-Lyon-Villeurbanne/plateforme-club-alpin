<?php

namespace App\Legacy;

use App\Repository\ContentInlineRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

readonly class ContentInline implements ServiceSubscriberInterface
{
    public function __construct(
        private ContainerInterface $locator,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return [
            ContentInlineRepository::class,
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getContent($code)
    {
        return $this->locator->get(ContentInlineRepository::class)->findOneBy(['code' => $code])->getContenu();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getLogo()
    {
        return $this->getContent('logo-img-src');
    }
}
