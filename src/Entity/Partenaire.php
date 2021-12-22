<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Partenaire.
 *
 * @ORM\Table(name="caf_partenaires")
 * @ORM\Entity
 */
class Partenaire
{
    /**
     * @var int
     *
     * @ORM\Column(name="part_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="part_name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="part_url", type="string", length=256, nullable=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="part_desc", type="string", length=500, nullable=false)
     */
    private $desc;

    /**
     * @var string
     *
     * @ORM\Column(name="part_image", type="string", length=100, nullable=false)
     */
    private $image;

    /**
     * @var int
     *
     * @ORM\Column(name="part_type", type="integer", nullable=false, options={"default": "1", "comment": "1=prive,2=public"})
     */
    private $type = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="part_enable", type="integer", nullable=false, options={"default": "1", "comment": "partenaire actif =1"})
     */
    private $enable = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="part_order", type="integer", nullable=false, options={"default": "999999"})
     */
    private $order = 999999;

    /**
     * @var int
     *
     * @ORM\Column(name="part_click", type="integer", nullable=false, options={"comment": "nb de cliques"})
     */
    private $click = '0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function setDesc(string $desc): self
    {
        $this->desc = $desc;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEnable(): ?int
    {
        return $this->enable;
    }

    public function setEnable(int $enable): self
    {
        $this->enable = $enable;

        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getClick(): ?int
    {
        return $this->click;
    }

    public function setClick(int $click): self
    {
        $this->click = $click;

        return $this;
    }
}
