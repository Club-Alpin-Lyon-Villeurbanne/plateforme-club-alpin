<?php

namespace App\Entity;

use App\Repository\FormationCompetenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_formation_competence')]
#[ORM\Index(columns: ['code_formation'], name: 'idx_formation_comp')]
#[ORM\Index(columns: ['code_competence'], name: 'idx_competence_form')]
#[ORM\UniqueConstraint(name: 'unique_formation_competence', columns: ['code_formation', 'code_competence'])]
#[ORM\Entity(repositoryClass: FormationCompetenceRepository::class)]
class FormationCompetence
{
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'code_formation', type: Types::STRING, length: 50, nullable: false)]
    private string $codeFormation;

    #[ORM\ManyToOne(targetEntity: Competence::class)]
    #[ORM\JoinColumn(name: 'code_competence', referencedColumnName: 'code_competence', nullable: false)]
    private Competence $competence;

    public function getId(): int
    {
        return $this->id;
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

    public function getCompetence(): Competence
    {
        return $this->competence;
    }

    public function setCompetence(Competence $competence): self
    {
        $this->competence = $competence;

        return $this;
    }
}