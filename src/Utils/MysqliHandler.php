<?php

namespace App\Utils;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MysqliHandler
{
    private LoggerInterface $logger;
    private ?\mysqli $mysqli;
    private RequestStack $requestStack;
    private string $kernelEnvironment;
    private TokenStorageInterface $tokenStorage;
    private ContainerBagInterface $params;

    public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger, RequestStack $requestStack, string $kernelEnvironment, ContainerBagInterface $params)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->params = $params;
        // Lazy loading: connection will be initialized on first use
    }

    private function initializeConnection(): void
    {
        if ($this->mysqli instanceof \mysqli) {
            return; // Already connected
        }

        $dbname = $this->params->get('legacy_env_DB_NAME');

        if ('test' === $this->kernelEnvironment) {
            $dbname .= '_test';
        }

        $this->mysqli = new \mysqli($this->params->get('legacy_env_DB_HOST'), $this->params->get('legacy_env_DB_USER'), $this->params->get('legacy_env_DB_PASSWORD'), $dbname, $this->params->get('legacy_env_DB_PORT'));

        if ($this->mysqli->connect_errno) {
            throw new \RuntimeException('Error while connecting to database');
        }

        $this->mysqli->set_charset('utf8mb4');
    }

    public function query(string $sql)
    {
        $this->initializeConnection();
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
                    $user = $u->getEmail() . ' (' . $u->getId() . ')';
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

    public function prepare($query)
    {
        $this->initializeConnection();
        return $this->mysqli->prepare($query);
    }

    public function escapeString($value)
    {
        $this->initializeConnection();
        return $this->mysqli->real_escape_string($value);
    }

    public function insertId()
    {
        $this->initializeConnection();
        return $this->mysqli->insert_id;
    }

    public function lastError()
    {
        $this->initializeConnection();
        return $this->mysqli->error;
    }

    public function affectedRows()
    {
        $this->initializeConnection();
        return $this->mysqli->affected_rows;
    }

    public function __destruct()
    {
        if ($this->mysqli instanceof \mysqli) {
            $this->mysqli->close();
            $this->mysqli = null;
        }
    }
}
