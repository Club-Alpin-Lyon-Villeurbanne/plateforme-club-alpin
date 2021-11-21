<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafUsertype.
 *
 * @ORM\Table(name="caf_usertype")
 * @ORM\Entity
 */
class CafUsertype
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_usertype", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUsertype;

    /**
     * @var bool
     *
     * @ORM\Column(name="hierarchie_usertype", type="boolean", nullable=false, options={"comment": "Ordre d'apparition des types"})
     */
    private $hierarchieUsertype;

    /**
     * @var string
     *
     * @ORM\Column(name="code_usertype", type="string", length=30, nullable=false)
     */
    private $codeUsertype;

    /**
     * @var string
     *
     * @ORM\Column(name="title_usertype", type="string", length=30, nullable=false)
     */
    private $titleUsertype;

    /**
     * @var bool
     *
     * @ORM\Column(name="limited_to_comm_usertype", type="boolean", nullable=false, options={"comment": "bool : ce type est (ou non) limité à une commission donnée"})
     */
    private $limitedToCommUsertype;

    public function getIdUsertype(): ?int
    {
        return $this->idUsertype;
    }

    public function getHierarchieUsertype(): ?bool
    {
        return $this->hierarchieUsertype;
    }

    public function setHierarchieUsertype(bool $hierarchieUsertype): self
    {
        $this->hierarchieUsertype = $hierarchieUsertype;

        return $this;
    }

    public function getCodeUsertype(): ?string
    {
        return $this->codeUsertype;
    }

    public function setCodeUsertype(string $codeUsertype): self
    {
        $this->codeUsertype = $codeUsertype;

        return $this;
    }

    public function getTitleUsertype(): ?string
    {
        return $this->titleUsertype;
    }

    public function setTitleUsertype(string $titleUsertype): self
    {
        $this->titleUsertype = $titleUsertype;

        return $this;
    }

    public function getLimitedToCommUsertype(): ?bool
    {
        return $this->limitedToCommUsertype;
    }

    public function setLimitedToCommUsertype(bool $limitedToCommUsertype): self
    {
        $this->limitedToCommUsertype = $limitedToCommUsertype;

        return $this;
    }
}
