<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Image.
 *
 * @ORM\Table(name="caf_img")
 *
 * @ORM\Entity
 */
class Image
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_img", type="integer", nullable=false)
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre_img", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var int
     *
     * @ORM\Column(name="galerie_img", type="integer", nullable=false)
     */
    private $galerie;

    /**
     * @var int
     *
     * @ORM\Column(name="evt_img", type="integer", nullable=false, options={"comment": "Une photo peut être directement liée à une sortie et non une galerie (ex : creéation d'evt)"})
     */
    private $evt;

    /**
     * @var int
     *
     * @ORM\Column(name="user_img", type="integer", nullable=false)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="titre_img", type="string", length=30, nullable=false)
     */
    private $titre;

    /**
     * @var string
     *
     * @ORM\Column(name="legende_img", type="string", length=200, nullable=false)
     */
    private $legende;

    /**
     * @var string
     *
     * @ORM\Column(name="fichier_img", type="string", length=200, nullable=false)
     */
    private $fichier;

    /**
     * @var bool
     *
     * @ORM\Column(name="vis_img", type="boolean", nullable=false, options={"default": "1"})
     */
    private $vis = true;

    /**
     * @var int
     *
     * @ORM\Column(name="status_img", type="integer", nullable=false, options={"default": "1"})
     */
    private $status = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getGalerie(): ?int
    {
        return $this->galerie;
    }

    public function setGalerie(int $galerie): self
    {
        $this->galerie = $galerie;

        return $this;
    }

    public function getEvt(): ?int
    {
        return $this->evt;
    }

    public function setEvt(int $evt): self
    {
        $this->evt = $evt;

        return $this;
    }

    public function getUser(): ?int
    {
        return $this->user;
    }

    public function setUser(int $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getLegende(): ?string
    {
        return $this->legende;
    }

    public function setLegende(string $legende): self
    {
        $this->legende = $legende;

        return $this;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(string $fichier): self
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getVis(): ?bool
    {
        return $this->vis;
    }

    public function setVis(bool $vis): self
    {
        $this->vis = $vis;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
