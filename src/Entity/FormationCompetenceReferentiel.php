<?php

namespace App\Entity;

use App\Repository\FormationCompetenceReferentielRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'formation_competence_referentiel')]
#[ORM\Index(columns: ['code_activite'], name: 'IDX_COMP_REF_ACTIVITE')]
#[ORM\UniqueConstraint(name: 'UNIQ_COMP_REF_INTITULE_ACT', columns: ['intitule', 'code_activite'])]
#[ORM\Entity(repositoryClass: FormationCompetenceReferentielRepository::class)]
class FormationCompetenceReferentiel
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private string $intitule;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $codeActivite = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $activite = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCodeActivite(): ?string
    {
        return $this->codeActivite;
    }

    public function setCodeActivite(?string $codeActivite): self
    {
        $this->codeActivite = $codeActivite;

        return $this;
    }

    public function getActivite(): ?string
    {
        return $this->activite;
    }

    public function setActivite(?string $activite): self
    {
        $this->activite = $activite;

        return $this;
    }
}
