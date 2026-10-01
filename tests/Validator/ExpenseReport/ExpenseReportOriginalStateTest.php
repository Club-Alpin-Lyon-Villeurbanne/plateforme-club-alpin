<?php

namespace App\Tests\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Validator\ExpenseReport\ExpenseReportOriginalState;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

class ExpenseReportOriginalStateTest extends TestCase
{
    public function testStatusLoadedFromDatabaseIsReturnedAsIs(): void
    {
        $state = $this->state(['status' => ExpenseReportStatusEnum::APPROVED]);

        $this->assertSame(ExpenseReportStatusEnum::APPROVED, $state->status(new ExpenseReport()));
    }

    public function testRawStatusKeptByDoctrineAfterAFlushIsConvertedToTheEnum(): void
    {
        $state = $this->state(['status' => 'accounted']);

        $this->assertSame(ExpenseReportStatusEnum::ACCOUNTED, $state->status(new ExpenseReport()));
    }

    public function testUnknownOriginalStatusIsNull(): void
    {
        $this->assertNull($this->state([])->status(new ExpenseReport()));
        $this->assertNull($this->state(['status' => 'inconnu'])->status(new ExpenseReport()));
    }

    public function testOriginalValueOfAField(): void
    {
        $state = $this->state(['details' => '{}', 'refundRequired' => false]);

        $this->assertSame('{}', $state->value(new ExpenseReport(), 'details'));
        $this->assertFalse($state->value(new ExpenseReport(), 'refundRequired'));
        $this->assertNull($state->value(new ExpenseReport(), 'statusComment'));
    }

    private function state(array $originalData): ExpenseReportOriginalState
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getOriginalEntityData')->willReturn($originalData);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        return new ExpenseReportOriginalState($entityManager);
    }
}
