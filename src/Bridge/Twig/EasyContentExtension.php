<?php

namespace App\Bridge\Twig;

use App\Legacy\ContentHtml;
use App\UserRights;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EasyContentExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices()
    {
        return [
            UserRights::class,
            ContentHtml::class,
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('easy_include', [$this, 'getEasyInclude'], ['is_safe' => ['html']]),
            new TwigFunction('allowed', [$this, 'isAllowed']),
        ];
    }

    public function isAllowed($code_userright, $param = '')
    {
        return $this->locator->get(UserRights::class)->allowed($code_userright, $param);
    }

    public function getEasyInclude($elt, $style = 'vide')
    {
        return $this->locator->get(ContentHtml::class)->getEasyInclude($elt, $style);
    }
}
