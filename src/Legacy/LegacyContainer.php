<?php

namespace App\Legacy;

use Symfony\Component\DependencyInjection\Argument\ServiceLocator;
use Symfony\Component\DependencyInjection\Container;

class LegacyContainer
{
    public static function get(string $name, ServiceLocator $container = null)
    {
        if (!$container) {
            global $kernel;
            $container = $kernel->getContainer();
        }
        return $container->get($name);
    }
    
    public static function getParameter(string $name, ServiceLocator $container = null)
    {
        if (!$container) {
            global $kernel;
            $container = $kernel->getContainer();
        }

        return $container->getParameter($name);
    }
}
