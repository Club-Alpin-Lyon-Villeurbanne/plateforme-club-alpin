<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NdfDemande.
 *
 * @ORM\Table(name="ndf_demande")
 *
 * @ORM\Entity
 */
class NdfDemande
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
     * @var bool
     *
     * @ORM\Column(name="remboursement", type="boolean", nullable=false, options={"default": false})
     */
    private $remboursement = FALSE;

    /**
     * @var string
     *
     * @ORM\Column(name="statut", type="string", length=30, nullable=false)
     * @Assert\Choice(choices = {"en_cours","envoye","approuve","renonce","rejete"})
     */
    private $statut;

    /**
     * @var string
     *
     * @ORM\Column(name="statut_commentaire", type="string", length=100, nullable=false, options={"comment": "Commentaire du statut"})
     */
    private $statutCommentaire;

    /**
     * @var Sortie
     *
     * @ORM\ManyToOne(targetEntity="Evt")
     *
     * @ORM\JoinColumn(name="sortie", referencedColumnName="id_evt", nullable=true)
     */
    private $sortie;

    /**
     * @var Demandeur
     *
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @ORM\JoinColumn(name="demandeur", referencedColumnName="id_user", nullable=true)
     */
    private $demandeur;

    public function __construct(
        Evt $sortie,
        User $demandeur,
        string $statut,
        string $statutCommentaire
    ) {
        $this->demandeur = $demandeur;
        $this->sortie = $sortie;
        $this->statut = $statut;
        $this->statutCommentaire = $statutCommentaire;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getStatutCommentaire(): ?string
    {
        return $this->statutCommentaire;
    }

    public function setStatutCommentaire(String $statutCommentaire): self
    {
        $this->statutCommentaire = $statutCommentaire;

        return $this;
    }

    public function getRemboursement(): bool
    {
        return $this->remboursement;
    }

    public function setRemboursement(bool $remboursement): self
    {
        $this->remboursement = $remboursement;

        return $this;
    }

    public function getDemandeur(): User
    {
        return $this->demandeur;
    }

    public function setDemandeur(User $demandeur): self
    {
        $this->demandeur = $demandeur;

        return $this;
    }

    public function getSortie(): Evt
    {
        return $this->sortie;
    }

    public function setSortie(Evt $sortie): self
    {
        $this->sortie = $sortie;

        return $this;
    }
}
