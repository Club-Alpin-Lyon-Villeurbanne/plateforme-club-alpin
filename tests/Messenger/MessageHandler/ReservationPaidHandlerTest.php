<?php

namespace App\Tests\Messenger\MessageHandler;

use App\Messenger\Message\ReservationPaid;
use App\Messenger\MessageHandler\ReservationPaidHandler;
use App\Service\LoxyaReservationService;
use PHPUnit\Framework\TestCase;

class ReservationPaidHandlerTest extends TestCase
{
    public function testInvokeCallsLoxyaService(): void
    {
        $loxyaService = $this->createMock(LoxyaReservationService::class);
        $loxyaService->expects($this->once())
            ->method('markReservationAsPaid')
            ->with(42, 'HA-12345');

        $handler = new ReservationPaidHandler($loxyaService);
        $handler(new ReservationPaid(42, 'HA-12345'));
    }

    public function testInvokeLetExceptionBubbleForRetry(): void
    {
        $loxyaService = $this->createMock(LoxyaReservationService::class);
        $loxyaService->method('markReservationAsPaid')
            ->willThrowException(new \RuntimeException('Loxya is down'));

        $handler = new ReservationPaidHandler($loxyaService);

        $this->expectException(\RuntimeException::class);
        $handler(new ReservationPaid(42, 'HA-12345'));
    }
}
