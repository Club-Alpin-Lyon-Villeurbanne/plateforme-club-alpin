<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NdfDemande.
 *
 * @ORM\Table(name="ndf_depense_minibus_loc")
 *
 * @ORM\Entity
 */
class NdfDepenseMinibusLoc
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="nbre_km", type="int", nullable=false)
     */
    private $nbreKm;

    /**
     * @var decimal
     *
     * @ORM\Column(name="prix_loc_km", type="decimal", nullable=false, options={"default": 0})
     */
    private $prixLocKm;

    /**
     * @var string
     *
     * @ORM\Column(name="url_justif_loc", type="string", length=100, nullable=true)
     */
    private $urlJustifLoc;

    /**
     * @var int
     *
     * @ORM\Column(name="nbre_passager", type="int", nullable=false)
     */
    private $nbrePassager;

    /**
     * @var decimal
     *
     * @ORM\Column(name="cout_essence", type="decimal", nullable=false, options={"default": 0})
     */
    private $coutEssence;

    /**
     * @var decimal
     *
     * @ORM\Column(name="frais_peage", type="decimal", nullable=false, options={"default": 0})
     */
    private $fraisPeage;


    /**
     * @var string
     *
     * @ORM\Column(name="url_justif_peage", type="string", length=100, nullable=true)
     */
    private $urlJustifPeage;

    /**
     * @var ndfDemande
     *
     * @ORM\ManyToOne(targetEntity="NdfDemande")
     *
     * @ORM\JoinColumn(name="ndf_demande_id", referencedColumnName="id", nullable=false)
     */

    private $ndfDemande;

    public function __construct()
    {
        $this->nbreKm = 0;
        $this->nbrePassager = 0;
        $this->coutEssence = 0;
        $this->fraisPeage = 0;
        $this->urlJustifPeage = null;
        $this->urlJustifLoc = null;
        $this->prixLocKm = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbreKm(): int
    {
        return $this->nbreKm;
    }

    public function setNbreKm(int $nbreKm): self
    {
        $this->nbreKm = $nbreKm;

        return $this;
    }

    public function getPrixLocKm(): string
    {
        return $this->prixLocKm;
    }

    public function setPrixLocKm(string $prixLocKm): self
    {
        $this->prixLocKm = $prixLocKm;

        return $this;
    }

    public function getNbrePassager(): int
    {
        return $this->nbrePassager;
    }

    public function setNbrePassager(int $nbrePassager): self
    {
        $this->nbrePassager = $nbrePassager;

        return $this;
    }

    public function getCoutEssence(): string
    {
        return $this->coutEssence;
    }

    public function setCoutEssence(string $coutEssence): self
    {
        $this->coutEssence = $coutEssence;

        return $this;
    }

    public function getFraisPeage(): string
    {
        return $this->fraisPeage;
    }

    public function setFraisPeage(string $fraisPeage): self
    {
        $this->fraisPeage = $fraisPeage;

        return $this;
    }

    public function getUrlJustifLoc(): string
    {
        return $this->urlJustifLoc;
    }

    public function setUrlJustifLoc(string $urlJustifLoc): self
    {
        $this->urlJustifLoc = $urlJustifLoc;

        return $this;
    }


    public function getUrlJustifPeage(): string
    {
        return $this->urlJustifPeage;
    }

    public function setUrlJustifPeage(string $urlJustifPeage): self
    {
        $this->urlJustifPeage = $urlJustifPeage;

        return $this;
    }

    public function getNdfDemande(): NdfDemande
    {
        return $this->ndfDemande;
    }

    public function setNdfDemande(NdfDemande $ndfDemande): self
    {
        $this->ndfDemande = $ndfDemande;

        return $this;
    }
}
