<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafEvtDestination.
 *
 * @ORM\Table(name="caf_evt_destination")
 * @ORM\Entity
 */
class CafEvtDestination
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
     * @ORM\Column(name="id_evt", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="id_destination", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idDestination;

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

    public function getIdEvt(): ?int
    {
        return $this->idEvt;
    }

    public function setIdEvt(int $idEvt): self
    {
        $this->idEvt = $idEvt;

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
