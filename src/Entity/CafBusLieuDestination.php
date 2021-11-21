<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafBusLieuDestination.
 *
 * @ORM\Table(name="caf_bus_lieu_destination")
 * @ORM\Entity
 */
class CafBusLieuDestination
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
     * @ORM\Column(name="id_bus", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idBus;

    /**
     * @var int
     *
     * @ORM\Column(name="id_destination", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idDestination;

    /**
     * @var int
     *
     * @ORM\Column(name="id_lieu", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idLieu;

    /**
     * @var string|null
     *
     * @ORM\Column(name="type_lieu", type="string", length=50, nullable=true, options={"comment": "Choisir entre : ramasse, reprise"})
     */
    private $typeLieu;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdBus(): ?int
    {
        return $this->idBus;
    }

    public function setIdBus(int $idBus): self
    {
        $this->idBus = $idBus;

        return $this;
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

    public function getIdLieu(): ?int
    {
        return $this->idLieu;
    }

    public function setIdLieu(int $idLieu): self
    {
        $this->idLieu = $idLieu;

        return $this;
    }

    public function getTypeLieu(): ?string
    {
        return $this->typeLieu;
    }

    public function setTypeLieu(?string $typeLieu): self
    {
        $this->typeLieu = $typeLieu;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
