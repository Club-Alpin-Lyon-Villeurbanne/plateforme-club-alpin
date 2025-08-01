<?php

namespace App\Entity;

use App\Repository\NiveauPratiqueReferentielRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_niveau_pratique_referentiel')]
#[ORM\Index(columns: ['cursus_niveau_id'], name: 'idx_cursus_niveau')]
#[ORM\Index(columns: ['code_activite'], name: 'idx_code_activite_ref')]
#[ORM\UniqueConstraint(name: 'unique_cursus_niveau', columns: ['cursus_niveau_id'])]
#[ORM\Entity(repositoryClass: NiveauPratiqueReferentielRepository::class)]
class NiveauPratiqueReferentiel
{
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'cursus_niveau_id', type: Types::INTEGER, nullable: false)]
    private int $cursusNiveauId;

    #[ORM\Column(name: 'code_activite', type: Types::STRING, length: 10, nullable: false)]
    private string $codeActivite;

    #[ORM\Column(name: 'activite', type: Types::STRING, length: 100, nullable: false)]
    private string $activite;

    #[ORM\Column(name: 'niveau', type: Types::STRING, length: 255, nullable: false)]
    private string $niveau;

    #[ORM\Column(name: 'libelle', type: Types::STRING, length: 255, nullable: false)]
    private string $libelle;

    #[ORM\Column(name: 'niveau_court', type: Types::STRING, length: 50, nullable: true)]
    private ?string $niveauCourt = null;

    #[ORM\Column(name: 'discipline', type: Types::STRING, length: 100, nullable: true)]
    private ?string $discipline = null;

    #[ORM\OneToMany(targetEntity: NiveauCompetence::class, mappedBy: 'niveauReferentiel')]
    private Collection $competences;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCursusNiveauId(): int
    {
        return $this->cursusNiveauId;
    }

    public function setCursusNiveauId(int $cursusNiveauId): self
    {
        $this->cursusNiveauId = $cursusNiveauId;

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

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getNiveauCourt(): ?string
    {
        return $this->niveauCourt;
    }

    public function setNiveauCourt(?string $niveauCourt): self
    {
        $this->niveauCourt = $niveauCourt;

        return $this;
    }

    public function getDiscipline(): ?string
    {
        return $this->discipline;
    }

    public function setDiscipline(?string $discipline): self
    {
        $this->discipline = $discipline;

        return $this;
    }

    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(NiveauCompetence $competence): self
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
            $competence->setNiveauReferentiel($this);
        }

        return $this;
    }

    public function removeCompetence(NiveauCompetence $competence): self
    {
        if ($this->competences->removeElement($competence)) {
            if ($competence->getNiveauReferentiel() === $this) {
                $competence->setNiveauReferentiel(null);
            }
        }

        return $this;
    }
}