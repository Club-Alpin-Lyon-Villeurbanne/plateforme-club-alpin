<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CommissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Commission.
 */
#[ORM\Table(name: 'caf_commission')]
#[ORM\Entity(repositoryClass: CommissionRepository::class)]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
    normalizationContext: ['groups' => ['commission:read']],
    order: ['ordre' => 'ASC'],
    security: "is_granted('ROLE_USER')",
)]
class Commission
{
    public const array CONFIGURABLE_FIELDS = ['difficulte', 'distance', 'denivele'];

    /**
     * @var int
     */
    #[ORM\Column(name: 'id_commission', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['event:read', 'article:read', 'commission:read'])]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'ordre_commission', type: 'integer', nullable: false)]
    #[Groups('commission:read')]
    private $ordre;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'vis_commission', type: 'boolean', nullable: false)]
    private $vis = true;

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_commission', type: 'string', length: 50, nullable: false, unique: true)]
    #[Groups('commission:read')]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title_commission', type: 'string', length: 30, nullable: false)]
    #[Groups(['commission:read', 'event:read'])]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    #[Groups('commission:read')]
    private $googleDriveId;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $mandatoryFields = self::CONFIGURABLE_FIELDS;

    /** @var Collection<int, BrevetReferentiel> */
    #[ORM\ManyToMany(targetEntity: BrevetReferentiel::class, mappedBy: 'commissions')]
    private Collection $brevets;

    public function __construct(string $title, string $code, int $ordre)
    {
        $this->title = $title;
        $this->code = $code;
        $this->ordre = $ordre;
        $this->brevets = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getVis(): ?bool
    {
        return $this->vis;
    }

    public function setVis(bool $vis): self
    {
        $this->vis = $vis;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getGoogleDriveId(): ?string
    {
        return $this->googleDriveId;
    }

    public function setGoogleDriveId(string $googleDriveId): self
    {
        $this->googleDriveId = $googleDriveId;

        return $this;
    }

    public function getMandatoryFields(): array
    {
        return $this->mandatoryFields;
    }

    public function setMandatoryFields(array $mandatoryFields): self
    {
        $this->mandatoryFields = $mandatoryFields;

        return $this;
    }

    /** @return Collection<int, BrevetReferentiel> */
    public function getBrevets(): Collection
    {
        return $this->brevets;
    }
}
