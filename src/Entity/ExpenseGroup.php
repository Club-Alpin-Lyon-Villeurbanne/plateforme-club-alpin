<?php

namespace App\Entity;

use App\Repository\ExpenseGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseGroupRepository::class)]
class ExpenseGroup implements \JsonSerializable
{
    /** Expense groups types (an enum in the database) */

    // will display a dropdown to choose the expense type
    public const TYPE_UNIQUE = 'unique';
    // will display a button to add a new expense with all the fields of the expense type
    public const TYPE_MULTIPLE = 'multiple';
    // will display all the expenses of the group one time only
    public const TYPE_RAW = 'raw';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'expenseGroup', targetEntity: ExpenseType::class)]
    private Collection $expenseTypes;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    public function __construct()
    {
        $this->expenseTypes = new ArrayCollection();
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
            $expenseType->setExpenseGroup($this);
        }

        return $this;
    }

    public function removeExpenseType(ExpenseType $expenseType): static
    {
        if ($this->expenseTypes->removeElement($expenseType)) {
            // set the owning side to null (unless already changed)
            if ($expenseType->getExpenseGroup() === $this) {
                $expenseType->setExpenseGroup(null);
            }
        }

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!\in_array($type, [self::TYPE_UNIQUE, self::TYPE_MULTIPLE, self::TYPE_RAW], true)) {
            throw new \InvalidArgumentException('Invalid expense group type');
        }

        $this->type = $type;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'expenseTypes' => $this->expenseTypes->toArray(),
        ];
    }
}
