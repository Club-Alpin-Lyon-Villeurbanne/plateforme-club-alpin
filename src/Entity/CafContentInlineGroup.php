<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafContentInlineGroup.
 *
 * @ORM\Table(name="caf_content_inline_group")
 * @ORM\Entity
 */
class CafContentInlineGroup
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_content_inline_group", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idContentInlineGroup;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_content_inline_group", type="integer", nullable=false)
     */
    private $ordreContentInlineGroup;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_content_inline_group", type="string", length=300, nullable=false)
     */
    private $nomContentInlineGroup;

    public function getIdContentInlineGroup(): ?int
    {
        return $this->idContentInlineGroup;
    }

    public function getOrdreContentInlineGroup(): ?int
    {
        return $this->ordreContentInlineGroup;
    }

    public function setOrdreContentInlineGroup(int $ordreContentInlineGroup): self
    {
        $this->ordreContentInlineGroup = $ordreContentInlineGroup;

        return $this;
    }

    public function getNomContentInlineGroup(): ?string
    {
        return $this->nomContentInlineGroup;
    }

    public function setNomContentInlineGroup(string $nomContentInlineGroup): self
    {
        $this->nomContentInlineGroup = $nomContentInlineGroup;

        return $this;
    }
}
