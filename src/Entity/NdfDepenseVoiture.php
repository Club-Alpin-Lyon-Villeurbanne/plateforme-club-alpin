<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NdfDemande.
 *
 * @ORM\Table(name="ndf_depense_voiture")
 *
 * @ORM\Entity
 */
class NdfDepenseVoiture
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
     * @var string
     *
     * @ORM\Column(name="commentaire", type="string", length=100, nullable=true, options={"comment": "Commentaire"})
     */
    private $commentaire;

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
        $this->fraisPeage = 0;
        $this->urlJustifPeage = null;
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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(String $commentaire): self
    {
        $this->commentaire = $commentaire;

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
