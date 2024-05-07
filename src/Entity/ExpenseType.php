<?php

namespace App\Entity;

use App\Repository\ExpenseTypeExpenseFieldTypeRepository;
use App\Repository\ExpenseTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Entity(repositoryClass: ExpenseTypeRepository::class)]
class ExpenseType implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'expenseType', targetEntity: Expense::class)]
    private Collection $expenses;

    #[ORM\OneToMany(mappedBy: 'expenseType', targetEntity: ExpenseTypeExpenseFieldType::class)]
    private Collection $expenseFieldTypeRelations;

    #[ORM\ManyToOne(inversedBy: 'expenseTypes')]
    private ?ExpenseGroup $expenseGroup = null;

    public function __construct()
    {
        $this->expenses = new ArrayCollection();
        $this->expenseFieldTypeRelations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, Expense>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(Expense $expense): static
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses->add($expense);
            $expense->setExpenseType($this);
        }

        return $this;
    }

    public function removeExpense(Expense $expense): static
    {
        if ($this->expenses->removeElement($expense)) {
            // set the owning side to null (unless already changed)
            if ($expense->getExpenseType() === $this) {
                $expense->setExpenseType(null);
            }
        }

        return $this;
    }

    public function getExpenseGroup(): ?ExpenseGroup
    {
        return $this->expenseGroup;
    }

    public function setExpenseGroup(?ExpenseGroup $expenseGroup): static
    {
        $this->expenseGroup = $expenseGroup;

        return $this;
    }

    /**
     * Get the value of expenseFieldTypeRelations
     */ 
    public function getExpenseFieldTypeRelations(): Collection
    {
        return $this->expenseFieldTypeRelations;
    }
    
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'fieldTypes' => array_map(function($expenseFieldTypeRelation) {
                return $expenseFieldTypeRelation->getExpenseFieldType();
            }, $this->expenseFieldTypeRelations->toArray()),
        ];
    }
}
