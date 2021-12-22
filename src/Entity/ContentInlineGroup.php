<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContentInlineGroup.
 *
 * @ORM\Table(name="caf_content_inline_group")
 * @ORM\Entity
 */
class ContentInlineGroup
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_content_inline_group", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_content_inline_group", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_content_inline_group", type="string", length=300, nullable=false)
     */
    private $nom;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }
}
