<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'caf_userright', options: ['comment' => 'stockage des actions possibles (matrice des droits)'])]
#[ORM\Entity]
class UserRight
{
    #[ORM\Column(name: 'id_userright', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id;

    #[ORM\Column(name: 'code_userright', type: 'string', length: 40, nullable: false)]
    private ?string $code;

    #[ORM\Column(name: 'title_userright', type: 'string', length: 100, nullable: false)]
    private ?string $title;

    #[ORM\Column(name: 'ordre_userright', type: 'integer', nullable: false)]
    private ?int $ordre;

    #[ORM\Column(name: 'parent_userright', type: 'string', length: 40, nullable: false, options: ['comment' => 'regroupement dans la matrice des droits'])]
    private ?string $parent;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function setParent(string $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
