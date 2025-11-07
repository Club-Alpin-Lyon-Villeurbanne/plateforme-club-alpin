<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CommissionRepository;
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

    #[ORM\Column(name: 'code_ffcam_brevet', type: Types::STRING, length: 5, nullable: true)]
    private ?string $codeFfcamBrevet = null;

    #[ORM\Column(name: 'code_ffcam_niveau', type: Types::STRING, length: 2, nullable: true)]
    private ?string $codeFfcamNiveau = null;

    #[ORM\Column(name: 'code_ffcam_formation', type: Types::STRING, length: 2, nullable: true)]
    private ?string $codeFfcamFormation = null;

    public function __construct(string $title, string $code, int $ordre)
    {
        $this->title = $title;
        $this->code = $code;
        $this->ordre = $ordre;
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

    public function getCodeFfcamBrevet(): ?string
    {
        return $this->codeFfcamBrevet;
    }

    public function setCodeFfcamBrevet(?string $codeFfcamBrevet): self
    {
        $this->codeFfcamBrevet = $codeFfcamBrevet;

        return $this;
    }

    public function getCodeFfcamNiveau(): ?string
    {
        return $this->codeFfcamNiveau;
    }

    public function setCodeFfcamNiveau(?string $codeFfcamNiveau): self
    {
        $this->codeFfcamNiveau = $codeFfcamNiveau;

        return $this;
    }

    public function getCodeFfcamFormation(): ?string
    {
        return $this->codeFfcamFormation;
    }

    public function setCodeFfcamFormation(?string $codeFfcamFormation): self
    {
        $this->codeFfcamFormation = $codeFfcamFormation;

        return $this;
    }
}
