<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CommissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Commission.
 */
#[ORM\Table(name: 'caf_commission')]
#[ORM\Entity(repositoryClass: CommissionRepository::class)]
#[ApiResource(
    operations: []
)]
class Commission
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id_commission', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups('event:read')]

    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'ordre_commission', type: 'integer', nullable: false)]
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
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title_commission', type: 'string', length: 30, nullable: false)]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private $googleDriveId;

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
}
