<?php

namespace App\Messenger\MessageHandler;

use App\Entity\AlertType;
use App\Entity\Evt;
use App\Messenger\Message\SortiePubliee;
use App\Messenger\Message\UserNotification;
use App\Repository\EvtRepository;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class SortiePublieeHandler
{
    public function __construct(
        private readonly EvtRepository $evtRepository,
        private readonly UserRepository $userRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(SortiePubliee $message): void
    {
        $evt = $this->evtRepository->find($message->id);

        if (!$evt) {
            return;
        }

        $commissionCode = $evt->getCommission()->getCode();

        foreach ($this->userRepository->findUsersIdWithAlert($commissionCode, AlertType::Sortie) as $userId) {
            $this->notifyUser($evt, $userId);
        }
    }

    private function notifyUser(Evt $sortie, int $userId): void
    {
        $this->messageBus->dispatch(new UserNotification(AlertType::Sortie, $sortie->getId(), $userId));
    }
}
