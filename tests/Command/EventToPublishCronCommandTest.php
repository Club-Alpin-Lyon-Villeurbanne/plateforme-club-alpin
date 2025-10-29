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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EventToPublishCronCommandTest extends TestCase
{
    public function testExecuteSendsEmailsToResponsables()
    {
        $evtRepository = $this->createMock(EvtRepository::class);
        $userAttrRepository = $this->createMock(UserAttrRepository::class);
        $mailer = $this->createMock(Mailer::class);
        $logger = $this->createMock(LoggerInterface::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $commission = $this->createMock(Commission::class);
        $commission->method('getTitle')->willReturn('Alpinisme');

        $event = $this->createMock(Evt::class);
        $event->method('getCommission')->willReturn($commission);
        $event->method('getCode')->willReturn('test-event');
        $event->method('getId')->willReturn(123);
        $event->method('getTitre')->willReturn('Test Event');

        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('resp@example.com');

        $userAttr = $this->createMock(UserAttr::class);
        $userAttr->method('getUser')->willReturn($user);

        $evtRepository->method('getAllEventsToPublish')->willReturn([$event]);
        $userAttrRepository->method('getResponsablesByCommission')->willReturn([$userAttr]);

        $urlGenerator->method('generate')->willReturn('https://example.com/test-url');

        $mailer->expects($this->once())
            ->method('send')
            ->with(
                $user,
                'transactional/rappel-sortie-a-valider-resp-commission',
                $this->callback(function ($params) {
                    return isset($params['sorties'])
                        && is_array($params['sorties'])
                        && isset($params['sorties'][0]['titre'])
                        && isset($params['sorties'][0]['url'])
                        && isset($params['manage_events_url']);
                })
            );

        $logger->expects($this->any())->method('info');
        $logger->expects($this->any())->method('error');

        $command = new EventToPublishCronCommand($evtRepository, $userAttrRepository, $mailer, $logger, $urlGenerator);
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
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $commission = $this->createMock(Commission::class);
        $commission->method('getTitle')->willReturn('Ski');

        $event = $this->createMock(Evt::class);
        $event->method('getCommission')->willReturn($commission);

        $evtRepository->method('getAllEventsToPublish')->willReturn([$event]);
        $userAttrRepository->method('getResponsablesByCommission')->willReturn([]);

        $mailer->expects($this->never())->method('send');
        $logger->expects($this->once())->method('error')
            ->with('Email reminder: no responsable for commission Ski');

        $command = new EventToPublishCronCommand($evtRepository, $userAttrRepository, $mailer, $logger, $urlGenerator);
        $tester = new CommandTester($command);
        $result = $tester->execute([]);

        $this->assertEquals(Command::SUCCESS, $result);
    }
}
