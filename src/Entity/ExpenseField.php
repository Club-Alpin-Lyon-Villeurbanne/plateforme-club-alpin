<?php

namespace App\Entity;

use App\Repository\ExpenseFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseFieldRepository::class)]
class ExpenseField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $justification_document = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Expense $expense = null;

    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseFieldType $fieldType = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJustificationDocument(): ?string
    {
        return $this->justification_document;
    }

    public function setJustificationDocument(?string $justification_document): static
    {
        $this->justification_document = $justification_document;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getExpense(): ?Expense
    {
        return $this->expense;
    }

    public function setExpense(?Expense $expense): static
    {
        $this->expense = $expense;

        return $this;
    }

    public function getFieldType(): ?ExpenseFieldType
    {
        return $this->fieldType;
    }

    public function setFieldType(?ExpenseFieldType $fieldType): static
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
