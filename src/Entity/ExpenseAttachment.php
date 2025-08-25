<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Controller\Api\ExpenseAttachmentController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'piece-jointe',
    operations: [
        new Post(
            uriTemplate: '/notes-de-frais/{expenseReportId}/pieces-jointes',
            controller: ExpenseAttachmentController::class,
            read: false,
            deserialize: false
        ),
        new GetCollection(
            uriTemplate: '/notes-de-frais/{expenseReportId}/pieces-jointes',
            uriVariables: [
                'expenseReportId' => new Link(
                    fromClass: ExpenseReport::class,
                    fromProperty: 'attachments'
                ),
            ],
        ),
        // new Delete(
        //     security: "is_granted('ROLE_USER') and is_granted('EDIT', object.getExpenseReport())"
        // )
    ],
    normalizationContext: ['groups' => ['attachment:read']],
    security: "is_granted('ROLE_USER')",
)]
#[ORM\Entity]
class ExpenseAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['attachment:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ExpenseReport::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseReport $expenseReport = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_user')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['attachment:read'])]
    private string $expenseId;

    #[ORM\Column(length: 255)]
    private string $fileName;

    #[ORM\Column(length: 255)]
    private string $filePath;

    #[Groups(['attachment:read'])]
    private string $fileUrl;

    private ?UploadedFile $file = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpenseId(): string
    {
        return $this->expenseId;
    }

    public function setExpenseId(string $expenseId): self
    {
        $this->expenseId = $expenseId;

        return $this;
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

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFileUrl(): string
    {
        return $this->fileUrl;
    }

    public function setFileUrl(string $fileUrl): self
    {
        $this->fileUrl = $fileUrl;

        return $this;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file): self
    {
        $this->file = $file;

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

    public function update(string $fileName, string $filePath): self
    {
        $this->fileName = $fileName;
        $this->filePath = $filePath;

        return $this;
    }
}
