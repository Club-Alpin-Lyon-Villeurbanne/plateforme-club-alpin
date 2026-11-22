<?php

namespace App\Tests\Messenger\MessageHandler;

use App\Entity\Evt;
use App\Messenger\Message\SortiePubliee;
use App\Messenger\MessageHandler\SortiePublieePushHandler;
use App\Repository\EvtRepository;
use App\Service\PushNotificationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SortiePublieePushHandlerTest extends TestCase
{
    #[DataProvider('provideSkipNewSortieAlert')]
    public function testItNotifiesUnlessNewSortieAlertIsSkipped(bool $skipNewSortieAlert, int $expectedNotifications): void
    {
        $evt = $this->createStub(Evt::class);
        $evt->method('isSkipNewSortieAlert')->willReturn($skipNewSortieAlert);

        $evtRepository = $this->createStub(EvtRepository::class);
        $evtRepository->method('find')->willReturn($evt);

        $pushNotificationService = $this->createMock(PushNotificationService::class);
        $pushNotificationService->expects($this->exactly($expectedNotifications))->method('notifyEvent')->with($evt);

        (new SortiePublieePushHandler($evtRepository, $pushNotificationService))(new SortiePubliee(42));
    }

    public static function provideSkipNewSortieAlert(): iterable
    {
        yield 'alerte envoyée' => [false, 1];
        yield 'alerte désactivée (groupe déjà constitué)' => [true, 0];
    }

    public function testNotFound(): void
    {
        $evtRepository = $this->createStub(EvtRepository::class);
        $evtRepository->method('find')->willReturn(null);

        $pushNotificationService = $this->createMock(PushNotificationService::class);
        $pushNotificationService->expects($this->never())->method('notifyEvent');

        (new SortiePublieePushHandler($evtRepository, $pushNotificationService))(new SortiePubliee(2500000));
    }
}
