<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Userright.
 *
 * @ORM\Table(name="caf_userright")
 * @ORM\Entity
 */
class Userright
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_userright", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code_userright", type="string", length=40, nullable=false)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="title_userright", type="string", length=100, nullable=false)
     */
    private $title;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_userright", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_userright", type="string", length=40, nullable=false)
     */
    private $parent;

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
