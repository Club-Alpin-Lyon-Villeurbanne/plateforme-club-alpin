<?php

namespace App\Entity;

use App\Repository\NiveauPratiqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'caf_niveau_pratique')]
#[ORM\Index(columns: ['cafnum_user'], name: 'idx_cafnum')]
#[ORM\Index(columns: ['code_activite'], name: 'idx_code_activite')]
#[ORM\Index(columns: ['code_competence'], name: 'idx_code_competence')]
#[ORM\Index(columns: ['date_validation'], name: 'idx_date_validation')]
#[ORM\Entity(repositoryClass: NiveauPratiqueRepository::class)]
class NiveauPratique
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

    #[ORM\Column(name: 'club', type: Types::STRING, length: 20, nullable: true)]
    private string $club;

    #[ORM\Column(name: 'code_activite', type: Types::STRING, length: 10, nullable: false)]
    private string $codeActivite;

    #[ORM\Column(name: 'activite', type: Types::STRING, length: 100, nullable: false)]
    private string $activite;

    #[ORM\Column(name: 'code_competence', type: Types::STRING, length: 15, nullable: false)]
    private string $codeCompetence;

    #[ORM\Column(name: 'niveau', type: Types::STRING, length: 255, nullable: false)]
    private string $niveau;

    #[ORM\Column(name: 'date_validation', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateValidation;

    #[ORM\Column(name: 'validation_par', type: Types::STRING, length: 255, nullable: true)]
    private string $validationPar;

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

    public function getClub(): string
    {
        return $this->club;
    }

    public function setClub(string $club): self
    {
        $this->club = $club;

        return $this;
    }

    public function getCodeActivite(): string
    {
        return $this->codeActivite;
    }

    public function setCodeActivite(string $codeActivite): self
    {
        $this->codeActivite = $codeActivite;

        return $this;
    }

    public function getActivite(): string
    {
        return $this->activite;
    }

    public function setActivite(string $activite): self
    {
        $this->activite = $activite;

        return $this;
    }

    public function getNiveau(): string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): self
    {
        $this->niveau = $niveau;

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

    public function getValidationPar(): string
    {
        return $this->validationPar;
    }

    public function setValidationPar(string $validationPar): self
    {
        $this->validationPar = $validationPar;

        return $this;
    }

    public function getCodeCompetence(): string
    {
        return $this->codeCompetence;
    }

    public function setCodeCompetence(string $codeCompetence): self
    {
        $this->codeCompetence = $codeCompetence;

        return $this;
    }
}
