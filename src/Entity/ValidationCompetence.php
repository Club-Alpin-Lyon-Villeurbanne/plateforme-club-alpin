<?php

namespace App\Entity;

use App\Repository\ValidationCompetenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'caf_validation_competence')]
#[ORM\Index(columns: ['cafnum_user'], name: 'idx_cafnum_competence')]
#[ORM\Index(columns: ['code_competence'], name: 'idx_code_competence')]
#[ORM\Index(columns: ['date_validation'], name: 'idx_date_validation_comp')]
#[ORM\UniqueConstraint(name: 'unique_user_competence', columns: ['user_id', 'code_competence'])]
#[ORM\Entity(repositoryClass: ValidationCompetenceRepository::class)]
class ValidationCompetence
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'cafnum_user', type: Types::STRING, length: 20, nullable: false)]
    private string $cafnumUser;

    #[ORM\ManyToOne(targetEntity: Competence::class)]
    #[ORM\JoinColumn(name: 'code_competence', referencedColumnName: 'code_competence', nullable: false)]
    private Competence $competence;

    #[ORM\Column(name: 'date_validation', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateValidation = null;

    #[ORM\Column(name: 'source_formation', type: Types::STRING, length: 50, nullable: true)]
    private ?string $sourceFormation = null;

    public function getId(): int
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

    public function getCafnumUser(): string
    {
        return $this->cafnumUser;
    }

    public function setCafnumUser(string $cafnumUser): self
    {
        $this->cafnumUser = $cafnumUser;

        return $this;
    }

    public function getCompetence(): Competence
    {
        return $this->competence;
    }

    public function setCompetence(Competence $competence): self
    {
        $this->competence = $competence;

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

    public function getSourceFormation(): ?string
    {
        return $this->sourceFormation;
    }

    public function setSourceFormation(?string $sourceFormation): self
    {
        $this->sourceFormation = $sourceFormation;

        return $this;
    }
}