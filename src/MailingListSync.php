<?php

namespace App;

use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\EvtRepository;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MailingListSync
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private Connection $connection;
    private string $appScriptId;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, Connection $connection, string $appScriptId)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->connection = $connection;
        $this->appScriptId = $appScriptId;
    }

    public function addToMailingList(int $userId, int $userType, string $commissionName)
    {
        if (!$this->appScriptId) {
            return;
        }

        // "encadrant" (4)
        // "responsable de commission" (5)
        // "benevole" (10)
        // "coencadrant" (11)
        // "stagiaire" (12)
        if (!in_array($userType, [4, 5, 10, 11, 12], true)) {
            return;
        }

        $commissionName = \array_slice(explode('-', $commissionName), -1)[0];

        $userEmail = $this->connection->prepare('SELECT email_user FROM `caf_user` AS C WHERE C.id_user = :id')
            ->executeQuery(['id' => $userId])
            ->fetchFirstColumn();

        if (!$userEmail || !$commissionName) {
            return;
        }
        $response = $this->client->request('GET', sprintf('https://script.google.com/a/macros/clubalpinlyon.fr/s/%s/exec?%s', $this->appScriptId, http_build_query([
            'email' => $userEmail,
            'commission' => $commissionName,
        ], '', '&')));

        if (!$response->getStatusCode() >= 400) {
            $this->logger->error('Error while subscribing a user to a mailing list', [
                'email' => $userEmail,
                'commission' => $commissionName,
            ]);
        }
    }
}
