<?php

namespace App\Tests\Command;

use App\Command\MailerLiteAccueilSync;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailerLiteService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;

class MailerLiteAccueilSyncTest extends TestCase
{
    private function makeUser(int $id, string $email, string $createdAt): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Jean');
        $user->setLastname('Dupont');
        $user->setCreatedAt(new \DateTime($createdAt));

        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }

    public function testLaSaisonBasculeAuPremierSeptembre(): void
    {
        $this->assertSame(2025, MailerLiteAccueilSync::seasonFor(new \DateTimeImmutable('2026-08-31')));
        $this->assertSame(2026, MailerLiteAccueilSync::seasonFor(new \DateTimeImmutable('2026-09-01')));
        $this->assertSame(2026, MailerLiteAccueilSync::seasonFor(new \DateTimeImmutable('2027-01-15')));
        $this->assertSame(2027, MailerLiteAccueilSync::seasonFor(new \DateTimeImmutable('2027-09-01')));
    }

    public function testNeTraiteAucuneSaisonAnterieureA2026(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('findForAccueilCircuit');

        $mailerLite = $this->createMock(MailerLiteService::class);
        $mailerLite->expects($this->never())->method('pushToGroup');

        $command = new MailerLiteAccueilSync($repository, $mailerLite, new NullLogger(), 'G1', 'G2', 'production');
        $tester = new CommandTester($command);
        // Une execution le 31 aout 2026 tombe sur la saison 2025 : on ne rattrape pas.
        $exitCode = $tester->execute(['--execute' => true, '--now' => '2026-08-31']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('anterieure au demarrage', $tester->getDisplay());
    }

    public function testOrienteNouveauxEtRenouvellementsVersLesBonsGroupes(): void
    {
        $nouveau = $this->makeUser(1, 'nouveau@example.com', '2026-09-10');
        $renouvelant = $this->makeUser(2, 'ancien@example.com', '2019-03-04');

        $repository = $this->createMock(UserRepository::class);
        $repository->method('findForAccueilCircuit')->willReturn([$nouveau, $renouvelant]);
        $repository->expects($this->once())->method('markAccueilSeason')
            ->with($this->equalTo([1, 2]), $this->equalTo(2026));

        $pushed = [];
        $mailerLite = $this->createMock(MailerLiteService::class);
        $mailerLite->method('removeFromGroup')->willReturn(true);
        $mailerLite->method('pushToGroup')->willReturnCallback(function (string $groupId, array $users) use (&$pushed) {
            $pushed[$groupId] = array_map(fn (User $u) => $u->getEmail(), $users);

            return ['total' => \count($users), 'imported' => \count($users), 'updated' => 0, 'failed' => 0, 'skipped' => 0];
        });

        $command = new MailerLiteAccueilSync($repository, $mailerLite, new NullLogger(), 'GROUPE_BIENVENUE', 'GROUPE_RENOUVELLEMENT', 'production');
        $tester = new CommandTester($command);
        $tester->execute(['--execute' => true, '--season' => 2026]);

        $this->assertSame(['nouveau@example.com'], $pushed['GROUPE_BIENVENUE']);
        $this->assertSame(['ancien@example.com'], $pushed['GROUPE_RENOUVELLEMENT']);
    }

    public function testNeFaitRienSiMailerLiteNestPasConfigure(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('findForAccueilCircuit');

        $mailerLite = $this->createMock(MailerLiteService::class);
        $mailerLite->expects($this->never())->method('pushToGroup');

        // Chambery et Clermont partagent ce depot et son cron.json, mais n'ont pas MailerLite.
        $command = new MailerLiteAccueilSync($repository, $mailerLite, new NullLogger(), '159667990712813289', '', 'production');
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['--execute' => true, '--season' => 2026]);

        $this->assertSame(0, $exitCode, 'Un club sans MailerLite ne doit pas faire echouer le cron');
        $this->assertStringContainsString('non configure', $tester->getDisplay());
    }

    public function testExecuteEstNeutraliseHorsProduction(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findForAccueilCircuit')->willReturn([$this->makeUser(1, 'a@example.com', '2026-09-10')]);
        $repository->expects($this->never())->method('markAccueilSeason');

        $mailerLite = $this->createMock(MailerLiteService::class);
        $mailerLite->expects($this->never())->method('pushToGroup');

        $command = new MailerLiteAccueilSync($repository, $mailerLite, new NullLogger(), 'G1', 'G2', 'staging');
        $tester = new CommandTester($command);
        $tester->execute(['--execute' => true, '--season' => 2026]);

        $this->assertStringContainsString('ignore hors production', $tester->getDisplay());
    }

    public function testDryRunNEnvoieRien(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findForAccueilCircuit')->willReturn([$this->makeUser(1, 'a@example.com', '2026-09-10')]);
        $repository->expects($this->never())->method('markAccueilSeason');

        $mailerLite = $this->createMock(MailerLiteService::class);
        $mailerLite->expects($this->never())->method('pushToGroup');

        $command = new MailerLiteAccueilSync($repository, $mailerLite, new NullLogger(), 'G1', 'G2', 'production');
        $tester = new CommandTester($command);
        $tester->execute(['--season' => 2026]);

        $this->assertStringContainsString('DRY-RUN', $tester->getDisplay());
    }

    public function testPlafondDeVolumeBloqueSansForce(): void
    {
        $users = [];
        for ($i = 1; $i <= 801; ++$i) {
            $users[] = $this->makeUser($i, "u{$i}@example.com", '2026-09-10');
        }

        $repository = $this->createMock(UserRepository::class);
        $repository->method('findForAccueilCircuit')->willReturn($users);

        $mailerLite = $this->createMock(MailerLiteService::class);
        $mailerLite->expects($this->never())->method('pushToGroup');

        $command = new MailerLiteAccueilSync($repository, $mailerLite, new NullLogger(), 'G1', 'G2', 'production');
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['--execute' => true, '--season' => 2026]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('--force', $tester->getDisplay());
    }

    public function testRetireDuGroupeAvantDAjouter(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findForAccueilCircuit')->willReturn([$this->makeUser(1, 'a@example.com', '2019-01-01')]);

        $mailerLite = $this->createMock(MailerLiteService::class);
        $mailerLite->expects($this->once())->method('removeFromGroup')
            ->with('a@example.com', 'GROUPE_RENOUVELLEMENT')->willReturn(true);
        // pushToGroup est appele pour chaque groupe cible, y compris le groupe bienvenue
        // reste vide ici : sans garde sur les tableaux vides, l'appel est sans effet cote API.
        $mailerLite->expects($this->exactly(2))->method('pushToGroup')
            ->willReturn(['total' => 1, 'imported' => 1, 'updated' => 0, 'failed' => 0, 'skipped' => 0]);

        $command = new MailerLiteAccueilSync($repository, $mailerLite, new NullLogger(), 'GROUPE_BIENVENUE', 'GROUPE_RENOUVELLEMENT', 'production');
        (new CommandTester($command))->execute(['--execute' => true, '--season' => 2026]);
    }
}
