<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\DateDeLaSortieFilter;
use App\Doctrine\Filter\RechercheSurLaSortieFilter;
use App\Dto\ExpenseReportCreateDto;
use App\Repository\ExpenseReportRepository;
use App\State\ExpenseReportCloneProcessor;
use App\State\ExpenseReportCreateProcessor;
use App\State\ExpenseReportPatchProcessor;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\Enums\ExpenseReportTypeEnum;
use App\Validator\ValidExpenseReport;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpenseReportRepository::class)]
#[ApiResource(
    shortName: 'note-de-frais',
    operations: [
        new Post(
            uriTemplate: '/notes-de-frais',
            input: ExpenseReportCreateDto::class,
            processor: ExpenseReportCreateProcessor::class,
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: '/notes-de-frais/{id}/clone',
            processor: ExpenseReportCloneProcessor::class,
            security: "is_granted('ROLE_USER')",
            deserialize: false,
            name: 'clone',
        ),
        new GetCollection(
            uriTemplate: '/notes-de-frais',
            security: "is_granted('ROLE_USER')",
        ),
        new GetCollection(
            uriTemplate: '/admin/notes-de-frais',
            security: "is_granted('ROLE_ADMIN') or is_granted('manage_expense_reports')",
        ),
        // Déclarée avant `/notes-de-frais/{id}`, qui la masquerait sinon. Filtrée par NotesAValider.
        new GetCollection(
            uriTemplate: '/notes-de-frais/a-valider',
            security: "is_granted('ROLE_USER')",
        ),
        new Get(
            uriTemplate: '/notes-de-frais/{id}',
            security: "is_granted('ROLE_ADMIN') or is_granted('access_expense_report', object)",
        ),
        new Patch(
            uriTemplate: '/notes-de-frais/{id}',
            security: "is_granted('access_expense_report', object)",
            denormalizationContext: ['groups' => ['report:write']],
            processor: ExpenseReportPatchProcessor::class,
        ),
    ],
    security: "is_granted('ROLE_USER')",
    normalizationContext: ['groups' => ['report:read', 'attachment:read', 'user:read', 'event:read', 'commission:read'], 'skip_null_values' => false],
)]

#[ApiFilter(SearchFilter::class, properties: [
    'event' => 'exact',
    'status' => 'exact',
    'type' => 'exact',
    'user.lastname' => 'partial',
])]
// Recherche et date de la sortie : une note hors sortie n'en sort pas.
#[ApiFilter(RechercheSurLaSortieFilter::class, properties: ['event.titre' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['refundRequired'])]
#[ApiFilter(DateDeLaSortieFilter::class, properties: ['event.startDate'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'event.startDate', 'user.lastname', 'status'])]

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
    #[Groups(['report:read', 'report:write'])]

    private ?ExpenseReportStatusEnum $status = null;

    // null : pas encore choisi (note hors sortie). Une note de sortie a toujours un choix.
    #[ORM\Column(nullable: true)]
    #[Groups(['report:read', 'report:write'])]
    private ?bool $refundRequired = true;

    #[ORM\ManyToOne(inversedBy: 'expenseReports')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_user')]
    #[Groups(['user:read'])]
    #[SerializedName('utilisateur')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'expenseReports')]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'id_evt')]
    #[Groups(['event:read'])]
    #[SerializedName('sortie')]
    private ?Evt $event = null;

    #[ORM\Column(length: 20, enumType: ExpenseReportTypeEnum::class, options: ['default' => 'sortie'])]
    #[Groups(['report:read'])]
    private ExpenseReportTypeEnum $type = ExpenseReportTypeEnum::SORTIE;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'id_commission')]
    #[Groups(['report:read', 'report:write'])]
    private ?Commission $commission = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['report:read', 'report:write'])]
    private ?string $objet = null;

    // Date de l'achat, ou date de la formation.
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['report:read', 'report:write'])]
    #[Context(normalizationContext: [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'], denormalizationContext: [DateTimeNormalizer::FORMAT_KEY => '!Y-m-d'])]
    private ?\DateTimeImmutable $dateDepense = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'Le lien vers la formation doit être une adresse web (https://…).')]
    #[Assert\Length(max: 255)]
    #[Groups(['report:read', 'report:write'])]
    private ?string $lienFormation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['report:read', 'report:write'])]
    private ?string $motPourResponsable = null;

    // Montants figés à chaque soumission d'une note hors sortie.
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalDeclare = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalRemboursable = null;

    #[ORM\Column]
    #[Groups(['report:read'])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => \DateTime::ATOM])]
    #[SerializedName('dateCreation')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['report:read', 'report:write'])]
    #[SerializedName('commentaireStatut')]
    private ?string $statusComment = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['report:read', 'report:write'])]

    private ?string $details = null;

    #[ORM\OneToMany(mappedBy: 'expenseReport', targetEntity: ExpenseAttachment::class, cascade: ['persist', 'remove'])]
    #[Groups(['attachment:read'])]
    #[SerializedName('piecesJointes')]
    private Collection $attachments;

    // Verrou optimiste : de deux écritures simultanées, la seconde est refusée au lieu d'écraser la
    // première (un envoi croisé avec une modification des lignes, deux décisions en même temps).
    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $version = 1;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    public static function nouvelleNoteHorsSortie(User $demandeur, ExpenseReportTypeEnum $type): self
    {
        if (!$type->estHorsSortie()) {
            throw new \InvalidArgumentException('Une note de sortie se crée à partir de sa sortie.');
        }

        $note = new self();
        $note->user = $demandeur;
        $note->type = $type;
        $note->status = ExpenseReportStatusEnum::DRAFT;
        $note->refundRequired = null;

        return $note;
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

    public function getType(): ExpenseReportTypeEnum
    {
        return $this->type;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(?string $objet): self
    {
        $this->objet = $objet;

        return $this;
    }

    public function getDateDepense(): ?\DateTimeImmutable
    {
        return $this->dateDepense;
    }

    public function setDateDepense(?\DateTimeImmutable $dateDepense): self
    {
        $this->dateDepense = $dateDepense;

        return $this;
    }

    public function getLienFormation(): ?string
    {
        return $this->lienFormation;
    }

    public function setLienFormation(?string $lienFormation): self
    {
        $this->lienFormation = $lienFormation;

        return $this;
    }

    public function getMotPourResponsable(): ?string
    {
        return $this->motPourResponsable;
    }

    public function setMotPourResponsable(?string $motPourResponsable): self
    {
        $this->motPourResponsable = $motPourResponsable;

        return $this;
    }

    /**
     * Les champs propres aux notes hors sortie, avec leur valeur : une note de sortie les laisse
     * vides, et seul le demandeur les modifie.
     *
     * @return array<string, mixed>
     */
    public function champsHorsSortie(): array
    {
        return [
            'commission' => $this->commission,
            'objet' => $this->objet,
            'dateDepense' => $this->dateDepense,
            'lienFormation' => $this->lienFormation,
            'motPourResponsable' => $this->motPourResponsable,
        ];
    }

    /**
     * « NOM Prénom NDF JJ/MM/AAAA objet » : ce que la trésorerie saisit en comptabilité pour une
     * note hors sortie.
     */
    public function libelleComptable(): string
    {
        return sprintf('%s %s NDF %s %s', mb_strtoupper((string) $this->user?->getLastname()), $this->user?->getFirstname(), $this->dateDepense?->format('d/m/Y'), $this->objet);
    }

    public function getTotalDeclare(): ?string
    {
        return $this->totalDeclare;
    }

    public function getTotalRemboursable(): ?string
    {
        return $this->totalRemboursable;
    }

    /**
     * Le taux kilométrique d'une formation est figé avec ses lignes, dans `details`, pour que ses
     * lignes de voiture restent cohérentes avec les montants figés.
     */
    public function figerMontants(float $totalDeclare, float $totalRemboursable, ?float $tauxKilometrique = null): self
    {
        $this->totalDeclare = number_format($totalDeclare, 2, '.', '');
        $this->totalRemboursable = number_format($totalRemboursable, 2, '.', '');

        if (null !== $tauxKilometrique) {
            $details = json_decode($this->details ?? '', true);
            $details = \is_array($details) ? $details : ['lignes' => []];
            $details['tauxKilometrique'] = $tauxKilometrique;
            $this->details = json_encode($details, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);
        }

        return $this;
    }

    public function tauxKilometriqueFige(): ?float
    {
        $taux = json_decode($this->details ?? '', true)['tauxKilometrique'] ?? null;

        return \is_int($taux) || \is_float($taux) ? (float) $taux : null;
    }

    /**
     * Lignes d'une note hors sortie, dans l'ordre de la saisie ; aucune si `details` n'est pas
     * une liste de lignes.
     *
     * @return list<array<string, mixed>>
     */
    public function lignes(): array
    {
        $lignes = json_decode($this->details ?? '', true)['lignes'] ?? null;
        if (!\is_array($lignes) || !array_is_list($lignes)) {
            return [];
        }

        // Une ligne mal formée reste comptée : les messages numérotent les lignes comme l'écran.
        return array_map(static fn (mixed $ligne): array => \is_array($ligne) ? $ligne : [], $lignes);
    }

    public function getVersion(): int
    {
        return $this->version;
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
