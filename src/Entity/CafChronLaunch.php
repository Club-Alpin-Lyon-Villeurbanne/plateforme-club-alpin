<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafChronLaunch.
 *
 * @ORM\Table(name="caf_chron_launch")
 * @ORM\Entity
 */
class CafChronLaunch
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_chron_launch", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idChronLaunch;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_chron_launch", type="bigint", nullable=false)
     */
    private $tspChronLaunch;

    public function getIdChronLaunch(): ?int
    {
        return $this->idChronLaunch;
    }

    public function getTspChronLaunch(): ?string
    {
        return $this->tspChronLaunch;
    }

    public function setTspChronLaunch(string $tspChronLaunch): self
    {
        $this->tspChronLaunch = $tspChronLaunch;

        return $this;
    }
}
