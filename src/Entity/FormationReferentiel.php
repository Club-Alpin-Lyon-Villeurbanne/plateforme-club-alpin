<?php

namespace App\Entity;

use App\Repository\FormationReferentielRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'formation_referentiel')]
#[ORM\Entity(repositoryClass: FormationReferentielRepository::class)]
class FormationReferentiel
{
    #[ORM\Id]
    #[ORM\Column(name: 'code_formation', type: 'string', length: 50)]
    private string $codeFormation;

    #[ORM\Column(name: 'intitule', type: 'string', length: 255)]
    private string $intitule;

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
