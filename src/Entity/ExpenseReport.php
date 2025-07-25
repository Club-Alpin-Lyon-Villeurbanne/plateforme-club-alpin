<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\ExpenseReportCreateDto;
use App\Repository\ExpenseReportRepository;
use App\State\ExpenseReportCloneProcessor;
use App\State\ExpenseReportCreateProcessor;
use App\State\ExpenseReportProvider;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Validator\ValidExpenseReport;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpenseReportRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/expense-reports',
            input: ExpenseReportCreateDto::class,
            processor: ExpenseReportCreateProcessor::class,
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: '/expense-reports/{id}/clone',
            processor: ExpenseReportCloneProcessor::class,
            security: "is_granted('ROLE_USER')",
            name: 'clone',
        ),
        new GetCollection(
            uriTemplate: '/expense-reports',
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            uriTemplate: '/expense-reports/{id}',
            security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
        ),
        new Patch(
            uriTemplate: '/expense-reports/{id}',
            security: 'object.getUser() == user or is_granted("validate_expense_report")',
            // normalizationContext: ['groups' => ['report:read', 'attachment:read', 'user:read', 'event:read']]
        ),
    ],
    provider: ExpenseReportProvider::class,
    security: "is_granted('ROLE_USER')",
    normalizationContext: ['groups' => ['report:read', 'attachment:read', 'user:read', 'event:read'], 'skip_null_values' => false],
    graphQlOperations: []
)]

#[ApiFilter(SearchFilter::class, properties: ['event' => 'exact'])]

#[HasLifecycleCallbacks]
#[ValidExpenseReport]
class ExpenseReport
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROUVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACCOUNTED = 'accounted';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['report:read'])]

    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Choice(
        callback: [ExpenseReportStatusEnum::class, 'cases'],
        message: 'Invalid status',
    )]
    #[Groups(['report:read'])]

    private ?ExpenseReportStatusEnum $status = null;

    #[ORM\Column]
    #[Groups(['report:read'])]

    private ?bool $refundRequired = true;

    #[ORM\ManyToOne(inversedBy: 'expenseReports')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_user')]
    #[Groups(['user:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'expenseReports')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_evt')]
    #[Groups(['event:read'])]
    private ?Evt $event = null;

    #[ORM\Column]
    #[Groups(['report:read'])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]

    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['report:read'])]

    private ?string $statusComment = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['report:read'])]

    private ?string $details = null;

    #[ORM\OneToMany(mappedBy: 'expenseReport', targetEntity: ExpenseAttachment::class, cascade: ['persist', 'remove'])]

    #[Groups(['attachment:read'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?ExpenseReportStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ExpenseReportStatusEnum $status): self
    {
        // if (!\in_array($status, ExpenseReportEnum::getConstants(), true)) {
        //     throw new \InvalidArgumentException('Expense report status must be one of : ' . implode(', ', ExpenseReportEnum::getConstants()) . '.');
        // }

        $this->status = $status;

        return $this;
    }

    public function isRefundRequired(): ?bool
    {
        return $this->refundRequired;
    }

    public function setRefundRequired(bool $refundRequired): self
    {
        $this->refundRequired = $refundRequired;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEvent(): ?Evt
    {
        return $this->event;
    }

    public function setEvent(?Evt $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStatusComment(): ?string
    {
        return $this->statusComment;
    }

    public function setStatusComment(?string $statusComment): self
    {
        $this->statusComment = $statusComment;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return Collection<int, ExpenseAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(ExpenseAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setExpenseReport($this);
        }

        return $this;
    }

    public function removeAttachment(ExpenseAttachment $attachment): self
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getExpenseReport() === $this) {
                $attachment->setExpenseReport(null);
            }
        }

        return $this;
    }
}
