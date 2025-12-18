<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\EmailMarketingSyncService;
use App\Service\MailerLiteService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EmailMarketingSyncServiceTest extends TestCase
{
    private LoggerInterface $logger;
    private MailerLiteService $mailerLiteService;
    private EmailMarketingSyncService $service;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailerLiteService = $this->createMock(MailerLiteService::class);
        $this->service = new EmailMarketingSyncService($this->logger, $this->mailerLiteService);
    }

    public function testSyncUsersWithoutMailerLiteService(): void
    {
        $service = new EmailMarketingSyncService($this->logger, null);

        $user = new User(1);
        $user->setEmail('test@example.com');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Email marketing sync skipped: MailerLite service not configured');

        $service->syncUsers($user);
    }

    public function testSyncUsersWithEmptyArray(): void
    {
        $this->logger->expects($this->never())->method('info');

        $this->service->syncUsers([]);
    }

    public function testSyncUsersSingleUserWithEmail(): void
    {
        $user = new User(1);
        $user->setEmail('test@example.com');

        $this->mailerLiteService->expects($this->once())
            ->method('syncNewMembers')
            ->with([$user])
            ->willReturn([
                'imported' => 1,
                'updated' => 0,
                'failed' => 0,
            ]);

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->service->syncUsers($user);
    }

    public function testSyncUsersMultipleUsersWithEmail(): void
    {
        $user1 = new User(1);
        $user1->setEmail('user1@example.com');

        $user2 = new User(2);
        $user2->setEmail('user2@example.com');

        $this->mailerLiteService->expects($this->once())
            ->method('syncNewMembers')
            ->willReturn([
                'imported' => 2,
                'updated' => 0,
                'failed' => 0,
            ]);

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->service->syncUsers([$user1, $user2]);
    }

    public function testSyncUsersFiltersUsersWithoutEmail(): void
    {
        $user1 = new User(1);
        $user1->setEmail('test@example.com');

        $user2 = new User(2);
        $user2->setEmail(null);

        $user3 = new User(3);
        $user3->setEmail('');

        // Only user1 should be passed to mailerLiteService
        $this->mailerLiteService->expects($this->once())
            ->method('syncNewMembers')
            ->with([$user1])
            ->willReturn([
                'imported' => 1,
                'updated' => 0,
                'failed' => 0,
            ]);

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->service->syncUsers([$user1, $user2, $user3]);
    }

    public function testSyncUsersWithAllUsersWithoutEmail(): void
    {
        $user1 = new User(1);
        $user1->setEmail(null);

        $user2 = new User(2);
        $user2->setEmail('');

        $this->mailerLiteService->expects($this->never())
            ->method('syncNewMembers');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('No users with email to sync');

        $this->service->syncUsers([$user1, $user2]);
    }

    public function testSyncUsersHandlesException(): void
    {
        $user = new User(1);
        $user->setEmail('test@example.com');

        $this->mailerLiteService->expects($this->once())
            ->method('syncNewMembers')
            ->willThrowException(new \Exception('API Error'));

        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with('MailerLite sync failed: API Error');

        $this->service->syncUsers($user);
    }

    public function testSyncUsersLogsResults(): void
    {
        $user1 = new User(1);
        $user1->setEmail('user1@example.com');

        $user2 = new User(2);
        $user2->setEmail('user2@example.com');

        $this->mailerLiteService->expects($this->once())
            ->method('syncNewMembers')
            ->willReturn([
                'imported' => 1,
                'updated' => 1,
                'failed' => 0,
            ]);

        $this->logger->expects($this->atLeastOnce())
            ->method('info')
            ->withConsecutive(
                [$this->stringContains('Synchronizing')],
                [$this->stringContains('MailerLite sync')]
            );

        $this->service->syncUsers([$user1, $user2]);
    }

    public function testSyncUsersSingleUserAsArray(): void
    {
        $user = new User(1);
        $user->setEmail('test@example.com');

        $this->mailerLiteService->expects($this->once())
            ->method('syncNewMembers')
            ->with([$user])
            ->willReturn([
                'imported' => 1,
                'updated' => 0,
                'failed' => 0,
            ]);

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->service->syncUsers([$user]);
    }

    public function testSyncUsersLargeUserBase(): void
    {
        $users = [];
        for ($i = 0; $i < 50; ++$i) {
            $user = new User($i + 1);
            $user->setEmail('user' . $i . '@example.com');
            $users[] = $user;
        }

        $this->mailerLiteService->expects($this->once())
            ->method('syncNewMembers')
            ->willReturn([
                'imported' => 50,
                'updated' => 0,
                'failed' => 0,
            ]);

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->service->syncUsers($users);
    }
}
