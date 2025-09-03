<?php

namespace App\Entity;

use App\Repository\CompetenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_competence')]
#[ORM\Entity(repositoryClass: CompetenceRepository::class)]
class Competence
{
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'code_activite', type: Types::STRING, length: 10, nullable: false)]
    private string $codeActivite;

    #[ORM\Column(name: 'activite', type: Types::STRING, length: 100, nullable: false)]
    private string $activite;

    #[ORM\Column(name: 'niveau', type: Types::STRING, length: 255, nullable: false)]
    private string $niveau;

    #[ORM\Column(name: 'theme', type: Types::STRING, length: 255, nullable: false)]
    private string $theme;

    #[ORM\Column(name: 'code_competence', type: Types::STRING, length: 15, nullable: false)]
    private string $code;

    #[ORM\Column(name: 'titre', type: Types::STRING, length: 255, nullable: true)]
    private string $titre;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

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

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }
}
