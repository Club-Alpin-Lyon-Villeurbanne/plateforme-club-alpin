<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafImg.
 *
 * @ORM\Table(name="caf_img")
 * @ORM\Entity
 */
class CafImg
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_img", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idImg;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_img", type="integer", nullable=false)
     */
    private $ordreImg;

    /**
     * @var int
     *
     * @ORM\Column(name="galerie_img", type="integer", nullable=false)
     */
    private $galerieImg;

    /**
     * @var int
     *
     * @ORM\Column(name="evt_img", type="integer", nullable=false, options={"comment": "Une photo peut être directement liée à une sortie et non une galerie (ex : creéation d'evt)"})
     */
    private $evtImg;

    /**
     * @var int
     *
     * @ORM\Column(name="user_img", type="integer", nullable=false)
     */
    private $userImg;

    /**
     * @var string
     *
     * @ORM\Column(name="titre_img", type="string", length=30, nullable=false)
     */
    private $titreImg;

    /**
     * @var string
     *
     * @ORM\Column(name="legende_img", type="string", length=200, nullable=false)
     */
    private $legendeImg;

    /**
     * @var string
     *
     * @ORM\Column(name="fichier_img", type="string", length=200, nullable=false)
     */
    private $fichierImg;

    /**
     * @var bool
     *
     * @ORM\Column(name="vis_img", type="boolean", nullable=false, options={"default": "1"})
     */
    private $visImg = true;

    /**
     * @var int
     *
     * @ORM\Column(name="status_img", type="integer", nullable=false, options={"default": "1"})
     */
    private $statusImg = 1;

    public function getIdImg(): ?int
    {
        return $this->idImg;
    }

    public function getOrdreImg(): ?int
    {
        return $this->ordreImg;
    }

    public function setOrdreImg(int $ordreImg): self
    {
        $this->ordreImg = $ordreImg;

        return $this;
    }

    public function getGalerieImg(): ?int
    {
        return $this->galerieImg;
    }

    public function setGalerieImg(int $galerieImg): self
    {
        $this->galerieImg = $galerieImg;

        return $this;
    }

    public function getEvtImg(): ?int
    {
        return $this->evtImg;
    }

    public function setEvtImg(int $evtImg): self
    {
        $this->evtImg = $evtImg;

        return $this;
    }

    public function getUserImg(): ?int
    {
        return $this->userImg;
    }

    public function setUserImg(int $userImg): self
    {
        $this->userImg = $userImg;

        return $this;
    }

    public function getTitreImg(): ?string
    {
        return $this->titreImg;
    }

    public function setTitreImg(string $titreImg): self
    {
        $this->titreImg = $titreImg;

        return $this;
    }

    public function getLegendeImg(): ?string
    {
        return $this->legendeImg;
    }

    public function setLegendeImg(string $legendeImg): self
    {
        $this->legendeImg = $legendeImg;

        return $this;
    }

    public function getFichierImg(): ?string
    {
        return $this->fichierImg;
    }

    public function setFichierImg(string $fichierImg): self
    {
        $this->fichierImg = $fichierImg;

        return $this;
    }

    public function getVisImg(): ?bool
    {
        return $this->visImg;
    }

    public function setVisImg(bool $visImg): self
    {
        $this->visImg = $visImg;

        return $this;
    }

    public function getStatusImg(): ?int
    {
        return $this->statusImg;
    }

    public function setStatusImg(int $statusImg): self
    {
        $this->statusImg = $statusImg;

        return $this;
    }
}
