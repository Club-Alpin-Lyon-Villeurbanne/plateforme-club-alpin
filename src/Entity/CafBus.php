<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafBus.
 *
 * @ORM\Table(name="caf_bus")
 * @ORM\Entity
 */
class CafBus
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="id_destination", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idDestination;

    /**
     * @var string
     *
     * @ORM\Column(name="intitule", type="string", length=50, nullable=false)
     */
    private $intitule;

    /**
     * @var int
     *
     * @ORM\Column(name="places_max", type="integer", nullable=false, options={"unsigned": true})
     */
    private $placesMax;

    /**
     * @var int|null
     *
     * @ORM\Column(name="places_disponibles", type="integer", nullable=true, options={"unsigned": true})
     */
    private $placesDisponibles;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdDestination(): ?int
    {
        return $this->idDestination;
    }

    public function setIdDestination(int $idDestination): self
    {
        $this->idDestination = $idDestination;

        return $this;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): self
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getPlacesMax(): ?int
    {
        return $this->placesMax;
    }

    public function setPlacesMax(int $placesMax): self
    {
        $this->placesMax = $placesMax;

        return $this;
    }

    public function getPlacesDisponibles(): ?int
    {
        return $this->placesDisponibles;
    }

    public function setPlacesDisponibles(?int $placesDisponibles): self
    {
        $this->placesDisponibles = $placesDisponibles;

        return $this;
    }
}
