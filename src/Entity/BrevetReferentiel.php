<?php

namespace App\Entity;

use App\Repository\BrevetReferentielRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'formation_brevet_referentiel')]
#[ORM\Index(columns: ['code_brevet'], name: 'idx_code_brevet', unique: true)]
#[ORM\Entity(repositoryClass: BrevetReferentielRepository::class)]
class BrevetReferentiel
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'code_brevet', type: Types::STRING, length: 50, nullable: false, unique: true)]
    private string $codeBrevet;

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

    public function getCodeBrevet(): string
    {
        return $this->codeBrevet;
    }

    public function setCodeBrevet(string $codeBrevet): self
    {
        $this->codeBrevet = $codeBrevet;

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
