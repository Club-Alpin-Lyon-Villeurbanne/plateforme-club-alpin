<?php

namespace App\Entity;

use App\Repository\ExpenseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
class Expense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $spent_amount = null;

    #[ORM\Column(nullable: true)]
    private ?int $refund_amount = null;

    #[ORM\OneToMany(mappedBy: 'expense', targetEntity: ExpenseField::class, orphanRemoval: true)]
    private Collection $fields;

    #[ORM\ManyToOne(inversedBy: 'expenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseType $expenseType = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(inversedBy: 'expenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseReport $expenseReport = null;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpentAmount(): ?int
    {
        return $this->spent_amount;
    }

    public function setSpentAmount(int $spent_amount): static
    {
        $this->spent_amount = $spent_amount;

        return $this;
    }

    public function getRefundAmount(): ?int
    {
        return $this->refund_amount;
    }

    public function setRefundAmount(?int $refund_amount): static
    {
        $this->refund_amount = $refund_amount;

        return $this;
    }

    /**
     * @return Collection<int, ExpenseField>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function addField(ExpenseField $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setExpense($this);
        }

        return $this;
    }

    public function removeField(ExpenseField $field): static
    {
        if ($this->fields->removeElement($field)) {
            // set the owning side to null (unless already changed)
            if ($field->getExpense() === $this) {
                $field->setExpense(null);
            }
        }

        return $this;
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

    public function getExpenseReport(): ?ExpenseReport
    {
        return $this->expenseReport;
    }

    public function setExpenseReport(?ExpenseReport $expenseReport): static
    {
        $this->expenseReport = $expenseReport;

        return $this;
    }
}
