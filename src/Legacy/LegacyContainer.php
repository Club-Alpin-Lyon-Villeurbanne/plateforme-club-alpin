<?php

namespace App\Legacy;

class LegacyContainer
{
    public static function get(string $name)
    {
        global $kernel;

        return $kernel->getContainer()->get($name);
    }

    public static function getParameter(string $name)
    {
        global $kernel;

        return $kernel->getContainer()->getParameter($name);
    }
}
