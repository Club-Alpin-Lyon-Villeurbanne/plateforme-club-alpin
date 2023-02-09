<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChronLaunch.
 *
 * @ORM\Table(name="caf_chron_launch")
 *
 * @ORM\Entity
 */
class ChronLaunch
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_chron_launch", type="integer", nullable=false)
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_chron_launch", type="bigint", nullable=false)
     */
    private $tsp;

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
}
