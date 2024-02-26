<?php

namespace App\Entity;

use App\Repository\ExpenseFieldTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Entity(repositoryClass: ExpenseFieldTypeRepository::class)]
class ExpenseFieldType implements JsonSerializable
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

    #[ORM\OneToMany(mappedBy: 'expenseFieldType', targetEntity: ExpenseTypeExpenseFieldType::class)]
    private Collection $expenseFieldTypeRelations;

    // defined manually in SortieController.php
    private array $flags = [];

    #[ORM\Column(length: 255)]
    private ?string $inputType = null;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->expenseFieldTypeRelations = new ArrayCollection();
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
     * Get the value of flags
     *
     * @return bool
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * Set the value of flags ([flagname => flagvalue])
     *
     * @param array $flags
     *
     * @return self
     */
    public function setFlags(array $flags): self
    {
        $this->flags = $flags;

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

    /**
     * Get the value of expenseFieldTypeRelations
     */ 
    public function getexpenseFieldTypeRelations()
    {
        return $this->expenseFieldTypeRelations;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'inputType' => $this->getInputType(),
            'fieldTypeId' => $this->getId(),
            'flags' => $this->getFlags(),
        ];
    }
}
