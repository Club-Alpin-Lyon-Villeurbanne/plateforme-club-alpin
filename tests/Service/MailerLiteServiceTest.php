<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\MailerLiteService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MailerLiteServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey = 'test-api-key-12345';
    private string $welcomeGroupId = 'group-123';
    private MailerLiteService $service;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );
    }

    public function testSyncNewMembersWithoutApiKey(): void
    {
        $service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            null,
            $this->welcomeGroupId,
            'production'
        );

        $user = new User(1);
        $user->setEmail('test@example.com');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MailerLite sync disabled'));

        $result = $service->syncNewMembers([$user]);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertEquals(0, $result['imported']);
    }

    public function testSyncNewMembersWithoutGroupId(): void
    {
        $service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            null,
            'production'
        );

        $user = new User(1);
        $user->setEmail('test@example.com');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MailerLite sync disabled'));

        $result = $service->syncNewMembers([$user]);

        $this->assertEquals(1, $result['skipped']);
    }

    public function testSyncNewMembersWithEmptyUserArray(): void
    {
        $result = $this->service->syncNewMembers([]);

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
    }

    public function testSyncNewMembersFiltersUsersWithoutEmail(): void
    {
        $user1 = new User(1);
        $user1->setEmail('user1@example.com');

        $user2 = new User(2);
        $user2->setEmail(null);

        $user3 = new User(3);
        $user3->setEmail('');

        $mockResponse = new MockResponse(json_encode([
            'imported' => 1,
            'updated' => 0,
            'failed' => 0,
        ]));

        $this->httpClient = new MockHttpClient([$mockResponse]);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $result = $this->service->syncNewMembers([$user1, $user2, $user3]);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['skipped']);
        $this->assertEquals(1, $result['imported']);
    }

    public function testSyncNewMembersAllUsersWithoutEmail(): void
    {
        $user1 = new User(1);
        $user1->setEmail(null);

        $user2 = new User(2);
        $user2->setEmail('');

        $result = $this->service->syncNewMembers([$user1, $user2]);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['skipped']);
        $this->assertEquals(0, $result['imported']);
    }

    public function testSyncNewMembersSuccessfulImport(): void
    {
        $user = new User(1);
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $mockResponse = new MockResponse(json_encode([
            'imported' => 1,
            'updated' => 0,
            'failed' => 0,
        ]), ['http_code' => 200]);

        $this->httpClient = new MockHttpClient([$mockResponse]);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $result = $this->service->syncNewMembers([$user]);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testSyncNewMembersWithMultipleUsers(): void
    {
        $users = [];
        for ($i = 0; $i < 5; ++$i) {
            $user = new User($i + 1);
            $user->setEmail("user{$i}@example.com");
            $user->setFirstname("User{$i}");
            $user->setLastname('Test');
            $users[] = $user;
        }

        $mockResponse = new MockResponse(json_encode([
            'imported' => 5,
            'updated' => 0,
            'failed' => 0,
        ]), ['http_code' => 201]);

        $this->httpClient = new MockHttpClient([$mockResponse]);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $result = $this->service->syncNewMembers($users);

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(5, $result['imported']);
    }

    public function testSyncNewMembersWithBatching(): void
    {
        $users = [];
        // Create 250 users to test batching (BATCH_SIZE is 100)
        for ($i = 0; $i < 250; ++$i) {
            $user = new User($i + 1);
            $user->setEmail("user{$i}@example.com");
            $user->setFirstname('User');
            $user->setLastname('Test');
            $users[] = $user;
        }

        $mockResponses = [];
        // First two batches: 100 users each
        $mockResponses[] = new MockResponse(json_encode([
            'imported' => 100,
            'updated' => 0,
            'failed' => 0,
        ]), ['http_code' => 200]);
        $mockResponses[] = new MockResponse(json_encode([
            'imported' => 100,
            'updated' => 0,
            'failed' => 0,
        ]), ['http_code' => 200]);
        // Third batch: 50 users
        $mockResponses[] = new MockResponse(json_encode([
            'imported' => 50,
            'updated' => 0,
            'failed' => 0,
        ]), ['http_code' => 200]);

        $this->httpClient = new MockHttpClient($mockResponses);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $result = $this->service->syncNewMembers($users);

        $this->assertEquals(250, $result['total']);
        $this->assertEquals(250, $result['imported']);
    }

    public function testSyncNewMembersHandlesApiError(): void
    {
        $user = new User(1);
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $mockResponse = new MockResponse('{"error": "Unauthorized"}', ['http_code' => 401]);

        $this->httpClient = new MockHttpClient([$mockResponse]);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $result = $this->service->syncNewMembers([$user]);

        $this->assertEquals(1, $result['failed']);
        $this->assertEquals(0, $result['imported']);
    }

    public function testSyncNewMembersLogsResults(): void
    {
        $user = new User(1);
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $mockResponse = new MockResponse(json_encode([
            'imported' => 1,
            'updated' => 0,
            'failed' => 0,
        ]), ['http_code' => 200]);

        $this->httpClient = new MockHttpClient([$mockResponse]);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->service->syncNewMembers([$user]);
    }

    public function testSyncNewMembersWithMixedValidAndInvalidEmails(): void
    {
        $user1 = new User(1);
        $user1->setEmail('valid1@example.com');

        $user2 = new User(2);
        $user2->setEmail(null);

        $user3 = new User(3);
        $user3->setEmail('valid2@example.com');

        $mockResponse = new MockResponse(json_encode([
            'imported' => 2,
            'updated' => 0,
            'failed' => 0,
        ]), ['http_code' => 200]);

        $this->httpClient = new MockHttpClient([$mockResponse]);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $result = $this->service->syncNewMembers([$user1, $user2, $user3]);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertEquals(2, $result['imported']);
    }

    public function testSyncNewMembersWithPartialResults(): void
    {
        $users = [];
        for ($i = 0; $i < 3; ++$i) {
            $user = new User($i + 1);
            $user->setEmail("user{$i}@example.com");
            $user->setFirstname('User');
            $user->setLastname('Test');
            $users[] = $user;
        }

        $mockResponse = new MockResponse(json_encode([
            'imported' => 2,
            'updated' => 0,
            'failed' => 1,
        ]), ['http_code' => 200]);

        $this->httpClient = new MockHttpClient([$mockResponse]);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $result = $this->service->syncNewMembers($users);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(1, $result['failed']);
    }

    public function testSyncNewMembersConfigurationLogging(): void
    {
        $user = new User(1);
        $user->setEmail('test@example.com');

        $mockResponse = new MockResponse(json_encode([
            'imported' => 1,
            'updated' => 0,
            'failed' => 0,
        ]), ['http_code' => 200]);

        $this->httpClient = new MockHttpClient([$mockResponse]);
        $this->service = new MailerLiteService(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->welcomeGroupId,
            'production'
        );

        $result = $this->service->syncNewMembers([$user]);

        // Verify the sync was successful
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['imported']);
    }

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

    public function testPushToGroupCibleLeGroupeDemande(): void
    {
        $urls = [];
        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$urls) {
            $urls[] = $url;

            return new MockResponse('{"imported":1,"updated":0,"failed":0}');
        });

        $service = new MailerLiteService($httpClient, new NullLogger(), 'cle-api', '111', 'production');
        $service->pushToGroup('999', [$this->makeUser('a@example.com')]);

        $this->assertStringContainsString('/groups/999/subscribers/import', $urls[0]);
    }

    public function testPushToGroupIgnoreLesEmailsDeDoublon(): void
    {
        $bodies = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$bodies) {
            $bodies[] = $options['body'] ?? '';

            return new MockResponse('{"imported":1,"updated":0,"failed":0}');
        });

        $service = new MailerLiteService($httpClient, new NullLogger(), 'cle-api', '111', 'production');
        $results = $service->pushToGroup('999', [
            $this->makeUser('doublon.690012345678-famille@example.com'),
            $this->makeUser('vrai@example.com'),
        ]);

        $this->assertSame(1, $results['skipped']);
        $this->assertStringNotContainsString('doublon.', $bodies[0]);
    }

    public function testRemoveFromGroupRechercheParEmailPuisSupprime(): void
    {
        $calls = [];
        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$calls) {
            $calls[] = $method . ' ' . $url;

            if (str_contains($url, '/subscribers/a%40example.com') || str_contains($url, '/subscribers/a@example.com')) {
                return new MockResponse('{"data":{"id":"555","groups":[{"id":"999"}]}}');
            }

            return new MockResponse('', ['http_code' => 204]);
        });

        $service = new MailerLiteService($httpClient, new NullLogger(), 'cle-api', '111', 'production');

        $this->assertTrue($service->removeFromGroup('a@example.com', '999'));
        $this->assertCount(2, $calls);
        $this->assertStringStartsWith('DELETE ', $calls[1]);
        $this->assertStringContainsString('/subscribers/555/groups/999', $calls[1]);
    }

    public function testRemoveFromGroupNeFaitRienSiAbonneInconnu(): void
    {
        $calls = 0;
        $httpClient = new MockHttpClient(function () use (&$calls) {
            ++$calls;

            return new MockResponse('{"message":"Not found"}', ['http_code' => 404]);
        });

        $service = new MailerLiteService($httpClient, new NullLogger(), 'cle-api', '111', 'production');

        $this->assertTrue($service->removeFromGroup('inconnu@example.com', '999'));
        $this->assertSame(1, $calls, 'Aucun DELETE ne doit suivre un abonne introuvable');
    }

    public function testRemoveFromGroupRenvoieFalseSiErreurServeur(): void
    {
        $calls = 0;
        $httpClient = new MockHttpClient(function () use (&$calls) {
            ++$calls;

            return new MockResponse('{"message":"Internal Server Error"}', ['http_code' => 500]);
        });

        $service = new MailerLiteService($httpClient, new NullLogger(), 'cle-api', '111', 'production');

        $this->assertFalse($service->removeFromGroup('a@example.com', '999'));
        $this->assertSame(1, $calls, 'Aucun DELETE ne doit suivre une erreur serveur sur la recherche');
    }

    public function testRemoveFromGroupAucunAppelHorsProduction(): void
    {
        $requests = 0;
        $httpClient = new MockHttpClient(function () use (&$requests) {
            ++$requests;

            return new MockResponse('{}');
        });

        $service = new MailerLiteService($httpClient, new NullLogger(), 'cle-api', '123', 'staging');

        $this->assertTrue($service->removeFromGroup('test@example.com', '999'));
        $this->assertSame(0, $requests, 'Aucune requête ne doit partir hors production');
    }
}
