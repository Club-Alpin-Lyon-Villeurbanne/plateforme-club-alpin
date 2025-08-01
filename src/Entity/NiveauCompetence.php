<?php

namespace App\Entity;

use App\Repository\NiveauCompetenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_niveau_competence')]
#[ORM\Index(columns: ['cursus_niveau_id'], name: 'idx_cursus_niveau_comp')]
#[ORM\Index(columns: ['code_competence'], name: 'idx_competence_niveau')]
#[ORM\UniqueConstraint(name: 'unique_niveau_competence', columns: ['cursus_niveau_id', 'code_competence'])]
#[ORM\Entity(repositoryClass: NiveauCompetenceRepository::class)]
class NiveauCompetence
{
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: NiveauPratiqueReferentiel::class, inversedBy: 'competences')]
    #[ORM\JoinColumn(name: 'cursus_niveau_id', referencedColumnName: 'cursus_niveau_id', nullable: false, onDelete: 'CASCADE')]
    private NiveauPratiqueReferentiel $niveauReferentiel;

    #[ORM\ManyToOne(targetEntity: Competence::class)]
    #[ORM\JoinColumn(name: 'code_competence', referencedColumnName: 'code_competence', nullable: false)]
    private Competence $competence;

    public function getId(): int
    {
        return $this->id;
    }

    public function getNiveauReferentiel(): NiveauPratiqueReferentiel
    {
        return $this->niveauReferentiel;
    }

    public function setNiveauReferentiel(?NiveauPratiqueReferentiel $niveauReferentiel): self
    {
        $this->niveauReferentiel = $niveauReferentiel;

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