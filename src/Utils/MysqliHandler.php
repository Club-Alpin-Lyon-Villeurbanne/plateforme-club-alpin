<?php

namespace App\Utils;

use Psr\Log\LoggerInterface;

class MysqliHandler
{
    private LoggerInterface $logger;
    private ?\mysqli $mysqli;
    private string $kernelEnvironment;

    public function __construct(LoggerInterface $logger, string $kernelEnvironment)
    {
        $this->logger = $logger;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->initializeConnection();
    }

    private function initializeConnection()
    {
        $conf = include __DIR__.'/../../legacy/app/db_config.php';

        $dbname = $conf['dbname'];

        if ('test' === $this->kernelEnvironment) {
            $dbname .= '_test';
        }

        $this->mysqli = new \mysqli($conf['host'], $conf['user'], $conf['password'], $dbname, $conf['port']);

        if ($this->mysqli->connect_errno) {
            exit("Impossible de se connecter à la base de données. Merci d'avertir l'administrateur.");
        }

        $this->mysqli->set_charset('utf8mb4');
    }

    public function query(string $sql)
    {
        $result = $this->mysqli->query($sql);

        if (!$result) {
            $this->logger->error(sprintf('SQL error: %s', $this->mysqli->error), [
                'error' => $this->mysqli->error,
                'sql' => $sql,
            ]);
        }

        return $result;
    }

    public function escapeString($value)
    {
        return $this->mysqli->real_escape_string($value);
    }

    public function insertId()
    {
        return $this->mysqli->insert_id;
    }

    public function lastError()
    {
        return $this->mysqli->error;
    }

    public function affectedRows()
    {
        return $this->mysqli->affected_rows;
    }
}
