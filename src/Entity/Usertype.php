<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_usertype', options: ['comment' => 'stockage des niveaux de responsabilité possibles (matrice des droits)'])]
#[ORM\Entity]
class Usertype
{
    #[ORM\Column(name: 'id_usertype', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id;

    #[ORM\Column(name: 'hierarchie_usertype', type: 'integer', nullable: false, options: ['comment' => "Ordre d'apparition des niveaux"])]
    private ?int $hierarchie;

    #[ORM\Column(name: 'code_usertype', type: 'string', length: 30, nullable: false)]
    private ?string $code;

    #[ORM\Column(name: 'title_usertype', type: 'string', length: 30, nullable: false)]
    private ?string $title;

    #[ORM\Column(name: 'limited_to_comm_usertype', type: 'boolean', nullable: false, options: ['comment' => 'booléen : ce niveau est (ou non) limité à une commission donnée'])]
    private ?bool $limitedToComm;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHierarchie(): ?int
    {
        return $this->hierarchie;
    }

    public function setHierarchie(int $hierarchie): self
    {
        $this->hierarchie = $hierarchie;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getLimitedToComm(): ?bool
    {
        return $this->limitedToComm;
    }

    public function setLimitedToComm(bool $limitedToComm): self
    {
        $this->limitedToComm = $limitedToComm;

        return $this;
    }
}
