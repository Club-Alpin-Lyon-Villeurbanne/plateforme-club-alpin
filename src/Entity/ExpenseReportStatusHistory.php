<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\ExpenseReportStatusHistoryRepository;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ExpenseReportStatusHistoryRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/expense-reports/{id}/history',
            uriVariables: [
                'id' => new Link(
                    fromClass: ExpenseReport::class,
                    toProperty: 'expenseReport'
                ),
            ],
            normalizationContext: ['groups' => ['report:read']],
            security: "is_granted('ROLE_USER')"
        ),
    ],
)]
class ExpenseReportStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ExpenseReport::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseReport $expenseReport = null;

    #[ORM\Column(enumType: ExpenseReportStatusEnum::class, nullable: true)]
    #[Groups(['report:read'])]
    private ?ExpenseReportStatusEnum $oldStatus = null;

    #[ORM\Column(enumType: ExpenseReportStatusEnum::class)]
    #[Groups(['report:read'])]
    private ExpenseReportStatusEnum $newStatus;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_user')]
    private ?User $changedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['report:read'])]

    private ?\DateTimeImmutable $changedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpenseReport(): ?ExpenseReport
    {
        return $this->expenseReport;
    }

    public function setExpenseReport(?ExpenseReport $expenseReport): self
    {
        $this->expenseReport = $expenseReport;

        return $this;
    }

    public function getOldStatus(): ?ExpenseReportStatusEnum
    {
        return $this->oldStatus;
    }

    public function setOldStatus(?ExpenseReportStatusEnum $oldStatus): self
    {
        $this->oldStatus = $oldStatus;

        return $this;
    }

    public function getNewStatus(): ExpenseReportStatusEnum
    {
        return $this->newStatus;
    }

    public function setNewStatus(ExpenseReportStatusEnum $newStatus): self
    {
        $this->newStatus = $newStatus;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): self
    {
        $this->changedBy = $changedBy;

        return $this;
    }

    public function getChangedAt(): ?\DateTimeImmutable
    {
        return $this->changedAt;
    }

    public function setChangedAt(\DateTimeImmutable $changedAt): self
    {
        $this->changedAt = $changedAt;

        return $this;
    }
}
