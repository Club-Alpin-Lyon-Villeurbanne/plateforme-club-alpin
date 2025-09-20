<?php

namespace App\Tests\Command;

use App\Command\EventToPublishCronCommand;
use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Mailer\Mailer;
use App\Repository\EvtRepository;
use App\Repository\UserAttrRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class EventToPublishCronCommandTest extends TestCase
{
    public function testExecuteSendsEmailsToResponsables()
    {
        $evtRepository = $this->createMock(EvtRepository::class);
        $userAttrRepository = $this->createMock(UserAttrRepository::class);
        $mailer = $this->createMock(Mailer::class);
        $logger = $this->createMock(LoggerInterface::class);

        $commission = $this->createMock(Commission::class);
        $commission->method('getTitle')->willReturn('Alpinisme');

        $event = $this->createMock(Evt::class);
        $event->method('getCommission')->willReturn($commission);

        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('resp@example.com');

        $userAttr = $this->createMock(UserAttr::class);
        $userAttr->method('getUser')->willReturn($user);

        $evtRepository->method('getAllEventsToPublish')->willReturn([$event]);
        $userAttrRepository->method('getResponsablesByCommission')->willReturn([$userAttr]);

        $mailer->expects($this->once())
            ->method('send')
            ->with(
                $user,
                'transactional/rappel-sortie-a-valider-resp-commission',
                $this->callback(function ($params) use ($event) {
                    return isset($params['sorties']) && \in_array($event, $params['sorties'], true);
                })
            );

        $logger->expects($this->any())->method('info');
        $logger->expects($this->any())->method('error');

        $command = new EventToPublishCronCommand($evtRepository, $userAttrRepository, $mailer, $logger);
        $tester = new CommandTester($command);
        $result = $tester->execute([]);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteHandlesNoResponsable()
    {
        $evtRepository = $this->createMock(EvtRepository::class);
        $userAttrRepository = $this->createMock(UserAttrRepository::class);
        $mailer = $this->createMock(Mailer::class);
        $logger = $this->createMock(LoggerInterface::class);

        $commission = $this->createMock(Commission::class);
        $commission->method('getTitle')->willReturn('Ski');

        $event = $this->createMock(Evt::class);
        $event->method('getCommission')->willReturn($commission);

        $evtRepository->method('getAllEventsToPublish')->willReturn([$event]);
        $userAttrRepository->method('getResponsablesByCommission')->willReturn([]);

        $mailer->expects($this->never())->method('send');
        $logger->expects($this->once())->method('error')
            ->with('Email reminder: no responsable for commission Ski');

        $command = new EventToPublishCronCommand($evtRepository, $userAttrRepository, $mailer, $logger);
        $tester = new CommandTester($command);
        $result = $tester->execute([]);

        $this->assertEquals(Command::SUCCESS, $result);
    }
}
