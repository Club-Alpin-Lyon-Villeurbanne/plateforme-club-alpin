<?php

namespace App\Entity;

use App\Repository\FormationReferentielFormationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'formation_referentiel_formation')]
#[ORM\UniqueConstraint(name: 'UNIQ_CODE_FORMATION', columns: ['code_formation'])]
#[ORM\Entity(repositoryClass: FormationReferentielFormationRepository::class)]
class FormationReferentielFormation
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'code_formation', type: Types::STRING, length: 50)]
    private string $codeFormation;

    #[ORM\Column(name: 'intitule', type: Types::STRING, length: 255)]
    private string $intitule;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

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

    public function getIntitule(): string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): self
    {
        $this->intitule = $intitule;

        return $this;
    }
}
