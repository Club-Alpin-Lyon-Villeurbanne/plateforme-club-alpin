<?php

namespace App\Bridge\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices()
    {
        return [
            SluggerInterface::class,
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('slugify', [$this, 'slugify']),
            new TwigFilter('random_item', [$this, 'getRandomItem']),
            new TwigFilter('intldate', [$this, 'formatDate']),
        ];
    }

    public function formatDate($date, string $format = 'd/MM/YYYY')
    {
        return (new \IntlDateFormatter(
            'fr-FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Europe/Paris',
            \IntlDateFormatter::GREGORIAN,
            $format
        ))->format($date);
    }

    public function slugify(string $string)
    {
        return $this->locator->get(SluggerInterface::class)->slug($string);
    }

    public function getRandomItem(array $items)
    {
        if (empty($items)) {
            return null;
        }

        return $items[array_rand($items)];
    }
}
