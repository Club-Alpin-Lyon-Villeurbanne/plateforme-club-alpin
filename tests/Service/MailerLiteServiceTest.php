<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\MailerLiteService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MailerLiteServiceTest extends TestCase
{
    private function makeUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Jean');
        $user->setLastname('Dupont');

        return $user;
    }

    public function testAucunAppelHorsProduction(): void
    {
        $requests = 0;
        $httpClient = new MockHttpClient(function () use (&$requests) {
            ++$requests;

            return new MockResponse('{}');
        });

        $service = new MailerLiteService($httpClient, new NullLogger(), 'cle-api', '123', 'staging');
        $results = $service->syncNewMembers([$this->makeUser('test@example.com')]);

        $this->assertSame(0, $requests, 'Aucune requête ne doit partir hors production');
        $this->assertSame(1, $results['skipped']);
    }

    public function testAppelEnProduction(): void
    {
        $requests = 0;
        $httpClient = new MockHttpClient(function () use (&$requests) {
            ++$requests;

            return new MockResponse('{"imported":1,"updated":0,"failed":0}');
        });

        $service = new MailerLiteService($httpClient, new NullLogger(), 'cle-api', '123', 'production');
        $service->syncNewMembers([$this->makeUser('test@example.com')]);

        $this->assertSame(1, $requests);
    }
}
