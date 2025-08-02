<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\SortiePubliee;
use App\Repository\EvtRepository;
use App\Service\PushNotificationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SortiePublieePushHandler
{
    public function __construct(
        private readonly EvtRepository $evtRepository,
        private readonly PushNotificationService $pushNotificationService,
    ) {
    }

    public function __invoke(SortiePubliee $message): void
    {
        $evt = $this->evtRepository->find($message->id);

        if (!$evt) {
            return;
        }

        $this->pushNotificationService->notifyEvent($evt);
    }
}
