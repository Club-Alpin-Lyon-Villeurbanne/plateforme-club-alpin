<?php

namespace App\Tests\EventListener\Entity;

use App\Entity\ExpenseReport;
use App\Tests\WebTestCase;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\EntityManagerInterface;

class ExpenseReportStatusHistoryListenerTest extends WebTestCase
{
    /**
     * Sans utilisateur, on ne sait pas qui a changé le statut : le flush échoue, et il doit
     * échouer avant que le mail de changement de statut ne parte.
     */
    public function testStatusChangeWithoutUserFailsBeforeAnyMailIsSent(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $this->signup();
        $report = new ExpenseReport();
        $report->setUser($user);
        $report->setEvent($this->createEvent($user));
        $report->setStatus(ExpenseReportStatusEnum::SUBMITTED);
        $report->setDetails('{"transport":{"type":"PUBLIC_TRANSPORT","ticketPrice":0},"accommodations":[],"others":[]}');
        $em->persist($report);
        $em->flush();

        $report->setStatus(ExpenseReportStatusEnum::APPROVED);
        try {
            $em->flush();
            $this->fail('Le flush aurait dû échouer sans utilisateur authentifié.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('utilisateur', $exception->getMessage());
        }

        $this->assertCount(0, $this->getMailerMessages());
    }
}
