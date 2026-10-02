<?php

namespace App\Tests\Api;

use App\Entity\EventParticipation;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Clonage (POST /api/notes-de-frais/{id}/clone) : réservé aux encadrants de la sortie,
 * pour réutiliser la note d'un co-encadrant.
 */
class ExpenseReportCloneTest extends ExpenseReportApiTestCase
{
    public function testSomeoneOutsideTheEventCannotCloneAReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $response = $this->cloneReport($this->stranger, $report);

        $this->assertResponseStatus(403, $response);
        $this->assertStringNotContainsString('ticketPrice', json_encode($response['body']));
    }

    public function testCoEncadrantOfTheSameEventCanCloneAReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $coEncadrant = $this->signup();
        $this->addToEvent($report, $coEncadrant, EventParticipation::ROLE_COENCADRANT);

        $response = $this->cloneReport($coEncadrant, $report);

        $this->assertResponseStatus(201, $response);
        $this->assertSame('draft', $response['body']['status']);
        $this->assertSame($coEncadrant->getId(), $response['body']['utilisateur']['id']);
    }

    public function testRequestBodyIsNotWrittenIntoTheOriginalReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $response = $this->cloneReport($this->owner, $report, ['refundRequired' => false, 'details' => self::INFLATED_DETAILS]);

        $this->assertResponseStatus(201, $response);
        $this->assertSame(self::DETAILS, $response['body']['details']);
    }

    private function addToEvent(ExpenseReport $report, User $user, string $role): void
    {
        $em = $this->em();
        $event = $em->find(ExpenseReport::class, $report->getId())->getEvent();
        $event->addParticipation($em->find(User::class, $user->getId()), $role, EventParticipation::STATUS_VALIDE);
        $em->flush();
        $em->clear();
    }
}
