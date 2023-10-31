<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChronOperation.
 *
 *
 */
#[ORM\Table(name: 'caf_chron_operation')]
#[ORM\Entity]
class ChronOperation
{
    /**
     * @var int
     *
     *
     *
     */
    #[ORM\Column(name: 'id_chron_operation', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'tsp_chron_operation', type: 'bigint', nullable: false)]
    private $tsp;

    /**
     * @var string
     */
    #[ORM\Column(name: 'code_chron_operation', type: 'string', length: 100, nullable: false)]
    private $code;

    /**
     * @var int
     */
    #[ORM\Column(name: 'parent_chron_operation', type: 'integer', nullable: false)]
    private $parent;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTsp(): ?string
    {
        return $this->tsp;
    }

    public function setTsp(string $tsp): self
    {
        $this->tsp = $tsp;

        return $this;
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

    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function setParent(int $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
