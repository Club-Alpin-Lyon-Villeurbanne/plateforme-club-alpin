<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafUserright.
 *
 * @ORM\Table(name="caf_userright")
 * @ORM\Entity
 */
class CafUserright
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_userright", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUserright;

    /**
     * @var string
     *
     * @ORM\Column(name="code_userright", type="string", length=40, nullable=false)
     */
    private $codeUserright;

    /**
     * @var string
     *
     * @ORM\Column(name="title_userright", type="string", length=100, nullable=false)
     */
    private $titleUserright;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_userright", type="integer", nullable=false)
     */
    private $ordreUserright;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_userright", type="string", length=40, nullable=false)
     */
    private $parentUserright;

    public function getIdUserright(): ?int
    {
        return $this->idUserright;
    }

    public function getCodeUserright(): ?string
    {
        return $this->codeUserright;
    }

    public function setCodeUserright(string $codeUserright): self
    {
        $this->codeUserright = $codeUserright;

        return $this;
    }

    public function getTitleUserright(): ?string
    {
        return $this->titleUserright;
    }

    public function setTitleUserright(string $titleUserright): self
    {
        $this->titleUserright = $titleUserright;

        return $this;
    }

    public function getOrdreUserright(): ?int
    {
        return $this->ordreUserright;
    }

    public function setOrdreUserright(int $ordreUserright): self
    {
        $this->ordreUserright = $ordreUserright;

        return $this;
    }

    public function getParentUserright(): ?string
    {
        return $this->parentUserright;
    }

    public function setParentUserright(string $parentUserright): self
    {
        $this->parentUserright = $parentUserright;

        return $this;
    }
}
