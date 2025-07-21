<?php

namespace App\Entity;

use App\Repository\FormationFFCAMRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationFFCAMRepository::class)]
#[ORM\Table(name: 'caf_formation_ffcam')]
#[ORM\Index(name: 'idx_formation_ffcam_adherent', columns: ['adherent_id'])]
#[ORM\Index(name: 'idx_formation_ffcam_code', columns: ['code'])]
#[ORM\HasLifecycleCallbacks]
class FormationFFCAM
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'adherent_id', referencedColumnName: 'id_user', nullable: false)]
    private ?User $adherent = null;

    #[ORM\Column(length: 50)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $intituleFormation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateObtention = null;

    #[ORM\Column(nullable: true)]
    private ?bool $professionnel = null;

    #[ORM\Column(name: 'commentaire_siege', type: Types::TEXT, nullable: true)]
    private ?string $commentaireSiege = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'date_modification', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateModification = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdherent(): ?User
    {
        return $this->adherent;
    }

    public function setAdherent(?User $adherent): static
    {
        $this->adherent = $adherent;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getIntituleFormation(): ?string
    {
        return $this->intituleFormation;
    }

    public function setIntituleFormation(string $intituleFormation): static
    {
        $this->intituleFormation = $intituleFormation;

        return $this;
    }

    public function getDateObtention(): ?\DateTimeInterface
    {
        return $this->dateObtention;
    }

    public function setDateObtention(\DateTimeInterface $dateObtention): static
    {
        $this->dateObtention = $dateObtention;

        return $this;
    }

    public function isProfessionnel(): ?bool
    {
        return $this->professionnel;
    }

    public function setProfessionnel(?bool $professionnel): static
    {
        $this->professionnel = $professionnel;

        return $this;
    }

    public function getCommentaireSiege(): ?string
    {
        return $this->commentaireSiege;
    }

    public function setCommentaireSiege(?string $commentaireSiege): static
    {
        $this->commentaireSiege = $commentaireSiege;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    #[ORM\PrePersist]
    public function setDateCreationValue(): void
    {
        if ($this->dateCreation === null) {
            $this->dateCreation = new \DateTime();
        }
        $this->setDateModificationValue();
    }

    #[ORM\PreUpdate]
    public function setDateModificationValue(): void
    {
        $this->dateModification = new \DateTime();
    }
}