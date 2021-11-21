<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafCommission.
 *
 * @ORM\Table(name="caf_commission")
 * @ORM\Entity
 */
class CafCommission
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_commission", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCommission;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_commission", type="integer", nullable=false)
     */
    private $ordreCommission;

    /**
     * @var bool
     *
     * @ORM\Column(name="vis_commission", type="boolean", nullable=false)
     */
    private $visCommission;

    /**
     * @var string
     *
     * @ORM\Column(name="code_commission", type="string", length=50, nullable=false)
     */
    private $codeCommission;

    /**
     * @var string
     *
     * @ORM\Column(name="title_commission", type="string", length=30, nullable=false)
     */
    private $titleCommission;

    public function getIdCommission(): ?int
    {
        return $this->idCommission;
    }

    public function getOrdreCommission(): ?int
    {
        return $this->ordreCommission;
    }

    public function setOrdreCommission(int $ordreCommission): self
    {
        $this->ordreCommission = $ordreCommission;

        return $this;
    }

    public function getVisCommission(): ?bool
    {
        return $this->visCommission;
    }

    public function setVisCommission(bool $visCommission): self
    {
        $this->visCommission = $visCommission;

        return $this;
    }

    public function getCodeCommission(): ?string
    {
        return $this->codeCommission;
    }

    public function setCodeCommission(string $codeCommission): self
    {
        $this->codeCommission = $codeCommission;

        return $this;
    }

    public function getTitleCommission(): ?string
    {
        return $this->titleCommission;
    }

    public function setTitleCommission(string $titleCommission): self
    {
        $this->titleCommission = $titleCommission;

        return $this;
    }
}
