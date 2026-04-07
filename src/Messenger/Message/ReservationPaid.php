<?php

namespace App\Messenger\Message;

class ReservationPaid
{
    public function __construct(
        public readonly int $reservationId,
        public readonly string $helloAssoPaymentId,
    ) {
    }
}
