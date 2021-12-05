<?php

global $kernel;

if (!function_exists('getMysqli')) {
    function getMysqli()
    {
        global $kernel;
        static $mysqli;

        if ($mysqli) {
            try {
                $mysqli->host_info;

                return $mysqli;
            } catch (\Throwable $e) {
            }
        }

        $conf = include __DIR__.'/../app/db_config.php';

        $dbname = $conf['dbname'];

        if ('test' === $kernel->getContainer()->getParameter('kernel.environment')) {
            $dbname .= '_test';
        }

        $mysqli = new mysqli($conf['host'], $conf['user'], $conf['password'], $dbname, $conf['port']);

        if ($mysqli->connect_errno) {
            exit("Impossible de se connecter à la base de données. Merci d'avertir l'administrateur.");
        }

        $mysqli->set_charset('utf8mb4');

        return $mysqli;
    }
}

return $mysqli = getMysqli();
