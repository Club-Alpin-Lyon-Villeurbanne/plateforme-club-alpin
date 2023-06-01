<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NdfDepenseHebergement.
 *
 * @ORM\Table(name="ndf_depense_hebergement")
 *
 * @ORM\Entity
 */
class NdfDepenseHebergement
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
     * @ORM\Column(name="ordre", type="int", nullable=false)
     */
    private $ordre;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire", type="string", length=100, nullable=true, options={"comment": "Commentaire"})
     */
    private $commentaire;

    /**
     * @var decimal
     *
     * @ORM\Column(name="montant", type="decimal", nullable=false, options={"default": 0})
     */
    private $montant;


    /**
     * @var string
     *
     * @ORM\Column(name="url_justif", type="string", length=100, nullable=true)
     */
    private $urlJustif;

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
        $this->ordre = 0;
        $this->montant = 0;
        $this->urlJustif = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

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

    public function getMontant(): string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getUrlJustif(): string
    {
        return $this->urlJustif;
    }

    public function setUrlJustif(string $urlJustif): self
    {
        $this->urlJustif = $urlJustif;

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
