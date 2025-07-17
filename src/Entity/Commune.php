<?php

namespace App\Entity;

use App\Repository\CommuneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'communes')]
#[ORM\Index(columns: ['code_postal'], name: 'code_postal')]
#[ORM\Entity(repositoryClass: CommuneRepository::class)]
class Commune
{
    #[ORM\Column(name: 'id_commune_insee', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id;

    #[ORM\Column(name: 'code_commune_insee', type: Types::STRING, nullable: false)]
    private string $codeCommuneInsee;

    #[ORM\Column(name: 'nom_commune', type: Types::STRING, nullable: false)]
    private string $nomCommune;

    #[ORM\Column(name: 'code_postal', type: Types::STRING, nullable: false)]
    private string $codePostal;

    #[ORM\Column(name: 'libelle_acheminement', type: Types::STRING, nullable: false)]
    private string $libelleAcheminement;

    #[ORM\Column(name: 'ligne5', type: Types::STRING, nullable: true)]
    private string $ligne5;

    public function __toString(): string
    {
        return $this->getNomCommune();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeCommuneInsee(): string
    {
        return $this->codeCommuneInsee;
    }

    public function setCodeCommuneInsee(string $codeCommuneInsee): self
    {
        $this->codeCommuneInsee = $codeCommuneInsee;

        return $this;
    }

    public function getNomCommune(): string
    {
        return $this->nomCommune;
    }

    public function setNomCommune(string $nomCommune): self
    {
        $this->nomCommune = $nomCommune;

        return $this;
    }

    public function getCodePostal(): string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getLibelleAcheminement(): string
    {
        return $this->libelleAcheminement;
    }

    public function setLibelleAcheminement(string $libelleAcheminement): self
    {
        $this->libelleAcheminement = $libelleAcheminement;

        return $this;
    }

    public function getLigne5(): string
    {
        return $this->ligne5;
    }

    public function setLigne5(string $ligne5): self
    {
        $this->ligne5 = $ligne5;

        return $this;
    }
}
