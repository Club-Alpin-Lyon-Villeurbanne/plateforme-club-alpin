<?php

namespace App\Entity;

use App\Repository\ExpenseTypeExpenseFieldTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseTypeExpenseFieldTypeRepository::class)]
class ExpenseTypeExpenseFieldType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseType $expenseType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseFieldType $expenseFieldType = null;

    #[ORM\Column]
    private ?bool $needsJustification = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpenseType(): ?ExpenseType
    {
        return $this->expenseType;
    }

    public function setExpenseType(?ExpenseType $expenseType): static
    {
        $this->expenseType = $expenseType;

        return $this;
    }

    public function getExpenseFieldType(): ?ExpenseFieldType
    {
        return $this->expenseFieldType;
    }

    public function setExpenseFieldType(?ExpenseFieldType $expenseFieldType): static
    {
        $this->expenseFieldType = $expenseFieldType;

        return $this;
    }

    public function getNeedsJustification(): ?bool
    {
        return $this->needsJustification;
    }

    public function setNeedsJustification(bool $needsJustification): static
    {
        $this->needsJustification = $needsJustification;

        return $this;
    }
}
