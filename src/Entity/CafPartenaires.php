<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafPartenaires.
 *
 * @ORM\Table(name="caf_partenaires")
 * @ORM\Entity
 */
class CafPartenaires
{
    /**
     * @var int
     *
     * @ORM\Column(name="part_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $partId;

    /**
     * @var string
     *
     * @ORM\Column(name="part_name", type="string", length=50, nullable=false)
     */
    private $partName;

    /**
     * @var string
     *
     * @ORM\Column(name="part_url", type="string", length=256, nullable=false)
     */
    private $partUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="part_desc", type="string", length=500, nullable=false)
     */
    private $partDesc;

    /**
     * @var string
     *
     * @ORM\Column(name="part_image", type="string", length=100, nullable=false)
     */
    private $partImage;

    /**
     * @var int
     *
     * @ORM\Column(name="part_type", type="integer", nullable=false, options={"default": "1", "comment": "1=prive,2=public"})
     */
    private $partType = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="part_enable", type="integer", nullable=false, options={"default": "1", "comment": "partenaire actif =1"})
     */
    private $partEnable = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="part_order", type="integer", nullable=false, options={"default": "999999"})
     */
    private $partOrder = 999999;

    /**
     * @var int
     *
     * @ORM\Column(name="part_click", type="integer", nullable=false, options={"comment": "nb de cliques"})
     */
    private $partClick = '0';

    public function getPartId(): ?int
    {
        return $this->partId;
    }

    public function getPartName(): ?string
    {
        return $this->partName;
    }

    public function setPartName(string $partName): self
    {
        $this->partName = $partName;

        return $this;
    }

    public function getPartUrl(): ?string
    {
        return $this->partUrl;
    }

    public function setPartUrl(string $partUrl): self
    {
        $this->partUrl = $partUrl;

        return $this;
    }

    public function getPartDesc(): ?string
    {
        return $this->partDesc;
    }

    public function setPartDesc(string $partDesc): self
    {
        $this->partDesc = $partDesc;

        return $this;
    }

    public function getPartImage(): ?string
    {
        return $this->partImage;
    }

    public function setPartImage(string $partImage): self
    {
        $this->partImage = $partImage;

        return $this;
    }

    public function getPartType(): ?int
    {
        return $this->partType;
    }

    public function setPartType(int $partType): self
    {
        $this->partType = $partType;

        return $this;
    }

    public function getPartEnable(): ?int
    {
        return $this->partEnable;
    }

    public function setPartEnable(int $partEnable): self
    {
        $this->partEnable = $partEnable;

        return $this;
    }

    public function getPartOrder(): ?int
    {
        return $this->partOrder;
    }

    public function setPartOrder(int $partOrder): self
    {
        $this->partOrder = $partOrder;

        return $this;
    }

    public function getPartClick(): ?int
    {
        return $this->partClick;
    }

    public function setPartClick(int $partClick): self
    {
        $this->partClick = $partClick;

        return $this;
    }
}
