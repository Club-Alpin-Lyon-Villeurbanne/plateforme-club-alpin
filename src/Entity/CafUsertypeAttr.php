<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafUsertypeAttr.
 *
 * @ORM\Table(name="caf_usertype_attr")
 * @ORM\Entity
 */
class CafUsertypeAttr
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_usertype_attr", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUsertypeAttr;

    /**
     * @var int
     *
     * @ORM\Column(name="type_usertype_attr", type="integer", nullable=false, options={"comment": "ID du type d'user (admin, modéro etc...)"})
     */
    private $typeUsertypeAttr;

    /**
     * @var int
     *
     * @ORM\Column(name="right_usertype_attr", type="integer", nullable=false, options={"comment": "ID du droit dans la table userright"})
     */
    private $rightUsertypeAttr;

    /**
     * @var string
     *
     * @ORM\Column(name="details_usertype_attr", type="string", length=100, nullable=false)
     */
    private $detailsUsertypeAttr;

    public function getIdUsertypeAttr(): ?int
    {
        return $this->idUsertypeAttr;
    }

    public function getTypeUsertypeAttr(): ?int
    {
        return $this->typeUsertypeAttr;
    }

    public function setTypeUsertypeAttr(int $typeUsertypeAttr): self
    {
        $this->typeUsertypeAttr = $typeUsertypeAttr;

        return $this;
    }

    public function getRightUsertypeAttr(): ?int
    {
        return $this->rightUsertypeAttr;
    }

    public function setRightUsertypeAttr(int $rightUsertypeAttr): self
    {
        $this->rightUsertypeAttr = $rightUsertypeAttr;

        return $this;
    }

    public function getDetailsUsertypeAttr(): ?string
    {
        return $this->detailsUsertypeAttr;
    }

    public function setDetailsUsertypeAttr(string $detailsUsertypeAttr): self
    {
        $this->detailsUsertypeAttr = $detailsUsertypeAttr;

        return $this;
    }
}
