<?php

namespace App\Entity;

use App\Repository\FormationValideeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'caf_formation_validee')]
#[ORM\Index(columns: ['cafnum_user'], name: 'idx_cafnum')]
#[ORM\Index(columns: ['code_formation'], name: 'idx_code_formation')]
#[ORM\Index(columns: ['date_validation'], name: 'idx_date_validation')]
#[ORM\Entity(repositoryClass: FormationValideeRepository::class)]
class FormationValidee
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false)]
    private ?User $user;

    #[ORM\Column(name: 'cafnum_user', type: Types::STRING, length: 20, nullable: false)]
    private string $cafnum;

    #[ORM\Column(name: 'nom_complet', type: Types::STRING, length: 255, nullable: false)]
    private string $nomComplet;

    #[ORM\Column(name: 'code_formation', type: Types::STRING, length: 50, nullable: false)]
    private string $codeFormation;

    #[ORM\Column(name: 'intitule_formation', type: Types::TEXT, nullable: false)]
    private string $intituleFormation;

    #[ORM\Column(name: 'date_validation', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateValidation;

    #[ORM\Column(name: 'numero_formation', type: Types::STRING, length: 50, nullable: true)]
    private string $numFormation;

    #[ORM\Column(name: 'formateur', type: Types::STRING, length: 255, nullable: true)]
    private string $formateur;

    #[ORM\Column(name: 'id_interne', type: Types::STRING, length: 20, nullable: true)]
    private string $idInterne;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

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

    public function getCafnum(): string
    {
        return $this->cafnum;
    }

    public function setCafnum(string $cafnum): self
    {
        $this->cafnum = $cafnum;

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): self
    {
        $this->nomComplet = $nomComplet;

        return $this;
    }

    public function getCodeFormation(): string
    {
        return $this->codeFormation;
    }

    public function setCodeFormation(string $codeFormation): self
    {
        $this->codeFormation = $codeFormation;

        return $this;
    }

    public function getIntituleFormation(): string
    {
        return $this->intituleFormation;
    }

    public function setIntituleFormation(string $intituleFormation): self
    {
        $this->intituleFormation = $intituleFormation;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeImmutable $dateValidation): self
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getNumFormation(): string
    {
        return $this->numFormation;
    }

    public function setNumFormation(string $numFormation): self
    {
        $this->numFormation = $numFormation;

        return $this;
    }

    public function getFormateur(): string
    {
        return $this->formateur;
    }

    public function setFormateur(string $formateur): self
    {
        $this->formateur = $formateur;

        return $this;
    }

    public function getIdInterne(): string
    {
        return $this->idInterne;
    }

    public function setIdInterne(string $idInterne): self
    {
        $this->idInterne = $idInterne;

        return $this;
    }
}
