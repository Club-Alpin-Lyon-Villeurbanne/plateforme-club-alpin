<?php

if (!function_exists('getMysqli')) {
    function getMysqli()
    {
        static $mysqli;

        if ($mysqli) {
            try {
                $mysqli->host_info;

                return $mysqli;
            } catch (\Throwable $e) {
            }
        }

        $conf = include __DIR__.'/../app/db_config.php';
        $mysqli = new mysqli($conf['host'], $conf['user'], $conf['password'], $conf['dbname'], $conf['port']);

        if ($mysqli->connect_errno) {
            exit("Impossible de se connecter à la base de données. Merci d'avertir l'administrateur.");
        }

        $mysqli->set_charset('UTF8');

        return $mysqli;
    }
}

return $mysqli = getMysqli();
