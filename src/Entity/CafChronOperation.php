<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafChronOperation.
 *
 * @ORM\Table(name="caf_chron_operation")
 * @ORM\Entity
 */
class CafChronOperation
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_chron_operation", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idChronOperation;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_chron_operation", type="bigint", nullable=false)
     */
    private $tspChronOperation;

    /**
     * @var string
     *
     * @ORM\Column(name="code_chron_operation", type="string", length=100, nullable=false)
     */
    private $codeChronOperation;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_chron_operation", type="integer", nullable=false)
     */
    private $parentChronOperation;

    public function getIdChronOperation(): ?int
    {
        return $this->idChronOperation;
    }

    public function getTspChronOperation(): ?string
    {
        return $this->tspChronOperation;
    }

    public function setTspChronOperation(string $tspChronOperation): self
    {
        $this->tspChronOperation = $tspChronOperation;

        return $this;
    }

    public function getCodeChronOperation(): ?string
    {
        return $this->codeChronOperation;
    }

    public function setCodeChronOperation(string $codeChronOperation): self
    {
        $this->codeChronOperation = $codeChronOperation;

        return $this;
    }

    public function getParentChronOperation(): ?int
    {
        return $this->parentChronOperation;
    }

    public function setParentChronOperation(int $parentChronOperation): self
    {
        $this->parentChronOperation = $parentChronOperation;

        return $this;
    }
}
