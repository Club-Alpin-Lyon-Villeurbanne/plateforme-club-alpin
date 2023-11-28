<?php

namespace App\Entity;

use App\Repository\ExpenseFieldTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseFieldTypeRepository::class)]
class ExpenseFieldType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'fieldType', targetEntity: ExpenseField::class)]
    private Collection $fields;

    #[ORM\ManyToMany(targetEntity: ExpenseType::class, mappedBy: 'fieldTypes')]
    private Collection $expenseTypes;

    // defined manually in SortieController.php
    private bool $needsJustification = false;

    #[ORM\Column(length: 255)]
    private ?string $inputType = null;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->expenseTypes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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
            $field->setFieldType($this);
        }

        return $this;
    }

    public function removeField(ExpenseField $field): static
    {
        if ($this->fields->removeElement($field)) {
            // set the owning side to null (unless already changed)
            if ($field->getFieldType() === $this) {
                $field->setFieldType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ExpenseType>
     */
    public function getExpenseTypes(): Collection
    {
        return $this->expenseTypes;
    }

    public function addExpenseType(ExpenseType $expenseType): static
    {
        if (!$this->expenseTypes->contains($expenseType)) {
            $this->expenseTypes->add($expenseType);
            $expenseType->addFieldType($this);
        }

        return $this;
    }

    public function removeExpenseType(ExpenseType $expenseType): static
    {
        if ($this->expenseTypes->removeElement($expenseType)) {
            $expenseType->removeFieldType($this);
        }

        return $this;
    }

    /**
     * Get the value of needsJustification
     *
     * @return bool
     */
    public function getNeedsJustification(): bool
    {
        return $this->needsJustification;
    }

    /**
     * Set the value of needsJustification
     *
     * @param bool $needsJustification
     *
     * @return self
     */
    public function setNeedsJustification(bool $needsJustification): self
    {
        $this->needsJustification = $needsJustification;

        return $this;
    }

    public function getInputType(): ?string
    {
        return $this->inputType;
    }

    public function setInputType(string $inputType): static
    {
        $this->inputType = $inputType;

        return $this;
    }
}
