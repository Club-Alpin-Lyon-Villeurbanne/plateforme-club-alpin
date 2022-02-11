<?php

namespace App\Utils;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MysqliHandler
{
    private LoggerInterface $logger;
    private ?\mysqli $mysqli;
    private RequestStack $requestStack;
    private string $kernelEnvironment;
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger, RequestStack $requestStack, string $kernelEnvironment)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
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
            throw new \RuntimeException('Error while connecting to database');
        }

        $this->mysqli->set_charset('utf8mb4');
    }

    public function query(string $sql)
    {
        $result = $this->mysqli->query($sql);

        if ($this->mysqli->errno > 0) {
            if ('prod' !== $this->kernelEnvironment) {
                throw new \RuntimeException(sprintf('Error while executing SQL query: "%s"', $this->mysqli->error));
            }

            $request = $this->requestStack->getMainRequest();
            $url = null;

            if ($request) {
                $url = $request->getUri();
            }

            $user = null;
            if ($token = $this->tokenStorage->getToken()) {
                if (($u = $token->getUser()) instanceof User) {
                    $user = $u->getEmail().' ('.$u->getId().')';
                }
            }

            $this->logger->error(sprintf('SQL error: %s', $this->mysqli->error), [
                'error' => $this->mysqli->error,
                'error number' => $this->mysqli->errno,
                'sql' => $sql,
                'exception' => new \RuntimeException(sprintf('SQL error: %s', $this->mysqli->error)),
                'url' => $url,
                'user' => $user,
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
