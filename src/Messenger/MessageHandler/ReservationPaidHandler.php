<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\ReservationPaid;
use App\Service\LoxyaReservationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ReservationPaidHandler
{
    public function __construct(
        private readonly LoxyaReservationService $loxyaReservationService,
    ) {
    }

    public function __invoke(ReservationPaid $message): void
    {
        $this->loxyaReservationService->markReservationAsPaid(
            $message->reservationId,
            $message->helloAssoPaymentId,
        );
    }
}
