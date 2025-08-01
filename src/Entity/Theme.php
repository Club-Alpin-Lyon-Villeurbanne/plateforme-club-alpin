<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_theme')]
#[ORM\UniqueConstraint(name: 'unique_code_theme', columns: ['code_theme'])]
#[ORM\Index(columns: ['ordre'], name: 'idx_ordre_theme')]
#[ORM\Entity(repositoryClass: ThemeRepository::class)]
class Theme
{
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'code_theme', type: Types::STRING, length: 20, nullable: false)]
    private string $codeTheme;

    #[ORM\Column(name: 'libelle', type: Types::STRING, length: 255, nullable: false)]
    private string $libelle;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'ordre', type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    private int $ordre = 0;

    #[ORM\Column(name: 'actif', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    private bool $actif = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCodeTheme(): string
    {
        return $this->codeTheme;
    }

    public function setCodeTheme(string $codeTheme): self
    {
        $this->codeTheme = $codeTheme;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }
}