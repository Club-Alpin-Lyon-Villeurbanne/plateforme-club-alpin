<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EvtDestination.
 *
 * @ORM\Table(name="caf_evt_destination")
 * @ORM\Entity
 */
class EvtDestination
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
     * @var Evt
     *
     * @ORM\OneToOne(targetEntity="Evt", inversedBy="destination")
     * @ORM\JoinColumn(name="id_evt", referencedColumnName="id_evt", nullable=false)
     */
    private $event;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Destination", inversedBy="events")
     * @ORM\JoinColumn(name="id_destination", referencedColumnName="id", nullable=false)
     */
    private $destination;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id_lieu_depose", type="integer", nullable=true, options={"unsigned": true})
     */
    private $idLieuDepose;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_depose", type="datetime", nullable=true)
     */
    private $dateDepose;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id_lieu_reprise", type="integer", nullable=true, options={"unsigned": true})
     */
    private $idLieuReprise;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_reprise", type="datetime", nullable=true)
     */
    private $dateReprise;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDestination(): Destination
    {
        return $this->destination;
    }

    public function getEvent(): Evt
    {
        return $this->event;
    }

    public function setEvent(Evt $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getIdLieuDepose(): ?int
    {
        return $this->idLieuDepose;
    }

    public function setIdLieuDepose(?int $idLieuDepose): self
    {
        $this->idLieuDepose = $idLieuDepose;

        return $this;
    }

    public function getDateDepose(): ?\DateTimeInterface
    {
        return $this->dateDepose;
    }

    public function setDateDepose(?\DateTimeInterface $dateDepose): self
    {
        $this->dateDepose = $dateDepose;

        return $this;
    }

    public function getIdLieuReprise(): ?int
    {
        return $this->idLieuReprise;
    }

    public function setIdLieuReprise(?int $idLieuReprise): self
    {
        $this->idLieuReprise = $idLieuReprise;

        return $this;
    }

    public function getDateReprise(): ?\DateTimeInterface
    {
        return $this->dateReprise;
    }

    public function setDateReprise(?\DateTimeInterface $dateReprise): self
    {
        $this->dateReprise = $dateReprise;

        return $this;
    }
}
