<?php

namespace App\Entity;

use App\Repository\FormationValidationFormationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'formation_validation_formation')]
#[ORM\Index(columns: ['user_id'], name: 'IDX_FORM_VAL_USER')]
#[ORM\Index(columns: ['code_formation'], name: 'IDX_FORM_VAL_CODE')]
#[ORM\Index(columns: ['date_validation'], name: 'IDX_FORM_VAL_DATE')]
#[ORM\UniqueConstraint(name: 'UNIQ_FORM_VAL_USER_ID_INTERNE', columns: ['user_id', 'id_interne'])]
#[ORM\Entity(repositoryClass: FormationValidationFormationRepository::class)]
class FormationValidationFormation
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: FormationReferentielFormation::class)]
    #[ORM\JoinColumn(name: 'code_formation', referencedColumnName: 'code_formation', nullable: true, onDelete: 'SET NULL')]
    private ?FormationReferentielFormation $formation = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $valide;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $numeroFormation = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $validateur = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $idInterne = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $intituleFormation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFormation(): ?FormationReferentielFormation
    {
        return $this->formation;
    }

    public function setFormation(?FormationReferentielFormation $formation): self
    {
        $this->formation = $formation;

        return $this;
    }

    public function isValide(): bool
    {
        return $this->valide;
    }

    public function setValide(bool $valide): self
    {
        $this->valide = $valide;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getNumeroFormation(): ?string
    {
        return $this->numeroFormation;
    }

    public function setNumeroFormation(?string $numeroFormation): self
    {
        $this->numeroFormation = $numeroFormation;

        return $this;
    }

    public function getValidateur(): ?string
    {
        return $this->validateur;
    }

    public function setValidateur(?string $validateur): self
    {
        $this->validateur = $validateur;

        return $this;
    }

    public function getIdInterne(): ?string
    {
        return $this->idInterne;
    }

    public function setIdInterne(?string $idInterne): self
    {
        $this->idInterne = $idInterne;

        return $this;
    }

    public function getIntituleFormation(): ?string
    {
        return $this->intituleFormation;
    }

    public function setIntituleFormation(?string $intituleFormation): self
    {
        $this->intituleFormation = $intituleFormation;

        return $this;
    }
}
