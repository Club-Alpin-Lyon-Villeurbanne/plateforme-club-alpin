<?php

namespace App\Entity;

use App\Repository\ExpenseReportStatusHistoryRepository;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\NiveauDeDecisionEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ExpenseReportStatusHistoryRepository::class)]
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

    // Message de la validation ou du renvoi ; le commentaire de statut d'une note hors sortie est effacé à son nouvel envoi.
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    // Décision sur une note hors sortie ; null pour une note de sortie et pour un autre changement de statut.
    #[ORM\Column(enumType: NiveauDeDecisionEnum::class, nullable: true)]
    private ?NiveauDeDecisionEnum $niveau = null;

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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getNiveau(): ?NiveauDeDecisionEnum
    {
        return $this->niveau;
    }

    public function setNiveau(?NiveauDeDecisionEnum $niveau): self
    {
        $this->niveau = $niveau;

        return $this;
    }
}
