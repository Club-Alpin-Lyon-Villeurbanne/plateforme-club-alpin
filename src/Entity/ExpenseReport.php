<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Api\ExpenseReportGet;
use App\Controller\Api\ExpenseReportList;
use App\Repository\ExpenseReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use JsonSerializable;

#[ORM\Entity(repositoryClass: ExpenseReportRepository::class)]
#[ApiResource(operations: [
    new Get(
        name: 'expense_report_get', 
        uriTemplate: '/expense-report/{id}',
        controller: ExpenseReportGet::class,
        stateless: false
    ),
    new Get(
        name: 'expense_report_list', 
        uriTemplate: '/expense-report',
        controller: ExpenseReportList::class,
        stateless: false
    )
])]
#[HasLifecycleCallbacks]
class ExpenseReport implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $status = null;

    #[ORM\Column]
    private ?bool $refund_required = null;

    #[ORM\OneToMany(mappedBy: 'expenseReport', targetEntity: Expense::class, orphanRemoval: true)]
    private Collection $expenses;

    #[ORM\ManyToOne(inversedBy: 'expenseReports')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_user')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'expenseReports')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_evt')]
    private ?Evt $event = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->expenses = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->created_at = new \DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isRefundRequired(): ?bool
    {
        return $this->refund_required;
    }

    public function setRefundRequired(bool $refund_required): static
    {
        $this->refund_required = $refund_required;

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
            $expense->setExpenseReport($this);
        }

        return $this;
    }

    public function removeExpense(Expense $expense): static
    {
        if ($this->expenses->removeElement($expense)) {
            // set the owning side to null (unless already changed)
            if ($expense->getExpenseReport() === $this) {
                $expense->setExpenseReport(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEvent(): ?Evt
    {
        return $this->event;
    }

    public function setEvent(?Evt $event): static
    {
        $this->event = $event;

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

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'owner' => $this->user->getId(),
            'event' => $this->event->getId(),
            'status' => $this->status,
            'refundRequired' => $this->refund_required,
            'createdAt' => $this->created_at->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
