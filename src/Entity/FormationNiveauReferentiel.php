<?php

namespace App\Entity;

use App\Repository\FormationNiveauReferentielRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'formation_niveau_referentiel')]
#[ORM\Index(columns: ['cursus_niveau_id'], name: 'IDX_FORM_NIV_REF_CURSUS')]
#[ORM\Index(columns: ['code_activite'], name: 'IDX_FORM_NIV_REF_ACTIVITE')]
#[ORM\UniqueConstraint(name: 'UNIQ_FORM_CURSUS_NIV', columns: ['cursus_niveau_id'])]
#[ORM\Entity(repositoryClass: FormationNiveauReferentielRepository::class)]
class FormationNiveauReferentiel
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $cursusNiveauId;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $codeActivite;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $activite;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $niveau;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $libelle;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $niveauCourt = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $discipline = null;

    public function getId(): ?int
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
}
