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

    /**
     * @var string
     *
     * @ORM\Column(name="type_transport", type="string", length=20, nullable=true, options={"comment": "Type de transport utilsé"})
     */
    private $typeTransport;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NdfDepenseVoiture", mappedBy="ndfDemande", cascade={"remove", "persist"})
     */
    private $ndfDepensesVoiture;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NdfDepenseMinibusLoc", mappedBy="ndfDemande", cascade={"remove", "persist"})
     */
    private $ndfDepensesMinibusLoc;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NdfDepenseMinibusClub", mappedBy="ndfDemande", cascade={"remove", "persist"})
     */
    private $ndfDepensesMinibusClub;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NdfDepenseCommun", mappedBy="ndfDemande", cascade={"remove", "persist"})
     */
    private $ndfDepensesCommun;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NdfDepenseHebergement", mappedBy="ndfDemande", cascade={"remove", "persist"})
     */
    private $ndfDepensesHebergement;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NdfDepenseAutre", mappedBy="ndfDemande", cascade={"remove", "persist"})
     */
    private $ndfDepensesAutre;

    public function __construct(
        Evt $sortie,
        User $demandeur,
        string $statut = 'en_cours',
        string $statutCommentaire = ''
    ) {
        $this->demandeur = $demandeur;
        $this->sortie = $sortie;
        $this->statut = $statut;
        $this->statutCommentaire = $statutCommentaire;
        $this->ndfDepensesVoiture = new ArrayCollection();
        $this->ndfDepensesMinibusLoc = new ArrayCollection();
        $this->ndfDepensesMinibusClub = new ArrayCollection();
        $this->ndfDepensesCommun = new ArrayCollection();
        $this->ndfDepensesHebergement = new ArrayCollection();
        $this->ndfDepensesAutre = new ArrayCollection();

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

    public function getTypeTransport(): ?string
    {
        return $this->typeTransport;
    }

    public function setTypeTransport(String $typeTransport): self
    {
        $this->typeTransport = $typeTransport;

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

    public function getNdfDepensesVoiture(): Collection
    {
        return $this->ndfDepensesVoiture;
    }

    public function addNdfDepensesVoiture(NdfDemande $ndfDepensesVoiture): self
    {
        if (!$this->ndfDepensesVoiture->contains($ndfDepensesVoiture)) {
            $this->ndfDepensesVoiture[] = $ndfDepensesVoiture;
            $ndfDepensesVoiture->setNdfDemande($this);
        }

        return $this;
    }

    public function removeNdfDepensesVoiture(NdfDemande $ndfDepensesVoiture): self
    {
        if ($this->ndfDepensesVoiture->contains($ndfDepensesVoiture)) {
            $this->ndfDepensesVoiture->removeElement($ndfDepensesVoiture);
            // set the owning side to null (unless already changed)
            if ($ndfDepensesVoiture->getNdfDemande() === $this) {
                $ndfDepensesVoiture->setNdfDemande(null);
            }
        }

        return $this;
    }

    public function getNdfDepensesMinibusLoc(): Collection
    {
        return $this->ndfDepensesMinibusLoc;
    }

    public function addNdfDepensesMinibusLoc(NdfDemande $ndfDepensesMinibusLoc): self
    {
        if (!$this->ndfDepensesMinibusLoc->contains($ndfDepensesMinibusLoc)) {
            $this->ndfDepensesMinibusLoc[] = $ndfDepensesMinibusLoc;
            $ndfDepensesMinibusLoc->setNdfDemande($this);
        }

        return $this;
    }

    public function removeNdfDepensesMinibusLoc(NdfDemande $ndfDepensesMinibusLoc): self
    {
        if ($this->ndfDepensesMinibusLoc->contains($ndfDepensesMinibusLoc)) {
            $this->ndfDepensesMinibusLoc->removeElement($ndfDepensesMinibusLoc);
            // set the owning side to null (unless already changed)
            if ($ndfDepensesMinibusLoc->getNdfDemande() === $this) {
                $ndfDepensesMinibusLoc->setNdfDemande(null);
            }
        }

        return $this;
    }

    public function getNdfDepensesMinibusClub(): Collection
    {
        return $this->ndfDepensesMinibusClub;
    }

    public function addNdfDepensesMinibusClub(NdfDemande $ndfDepensesMinibusClub): self
    {
        if (!$this->ndfDepensesMinibusClub->contains($ndfDepensesMinibusClub)) {
            $this->ndfDepensesMinibusClub[] = $ndfDepensesMinibusClub;
            $ndfDepensesMinibusClub->setNdfDemande($this);
        }

        return $this;
    }

    public function removeNdfDepensesMinibusClub(NdfDemande $ndfDepensesMinibusClub): self
    {
        if ($this->ndfDepensesMinibusClub->contains($ndfDepensesMinibusClub)) {
            $this->ndfDepensesMinibusClub->removeElement($ndfDepensesMinibusClub);
            // set the owning side to null (unless already changed)
            if ($ndfDepensesMinibusClub->getNdfDemande() === $this) {
                $ndfDepensesMinibusClub->setNdfDemande(null);
            }
        }

        return $this;
    }

    public function getNdfDepensesCommun(): Collection
    {
        return $this->ndfDepensesCommun;
    }

    public function addNdfDepensesCommun(NdfDemande $ndfDepensesCommun): self
    {
        if (!$this->ndfDepensesCommun->contains($ndfDepensesCommun)) {
            $this->ndfDepensesCommun[] = $ndfDepensesCommun;
            $ndfDepensesCommun->setNdfDemande($this);
        }

        return $this;
    }

    public function removeNdfDepensesCommun(NdfDemande $ndfDepensesCommun): self
    {
        if ($this->ndfDepensesCommun->contains($ndfDepensesCommun)) {
            $this->ndfDepensesCommun->removeElement($ndfDepensesCommun);
            // set the owning side to null (unless already changed)
            if ($ndfDepensesCommun->getNdfDemande() === $this) {
                $ndfDepensesCommun->setNdfDemande(null);
            }
        }

        return $this;
    }

    public function getNdfDepensesHebergement(): Collection
    {
        return $this->ndfDepensesHebergement;
    }

    public function addNdfDepensesHebergement(NdfDemande $ndfDepensesHebergement): self
    {
        dd('yo');
        if (!$this->ndfDepensesHebergement->contains($ndfDepensesHebergement)) {
            $this->ndfDepensesHebergement[] = $ndfDepensesHebergement;
            $ndfDepensesHebergement->setNdfDemande($this);
        }

        return $this;
    }

    public function removeNdfDepensesHebergement(NdfDemande $ndfDepensesHebergement): self
    {
        if ($this->ndfDepensesHebergement->contains($ndfDepensesHebergement)) {
            $this->ndfDepensesHebergement->removeElement($ndfDepensesHebergement);
            // set the owning side to null (unless already changed)
            if ($ndfDepensesHebergement->getNdfDemande() === $this) {
                $ndfDepensesHebergement->setNdfDemande(null);
            }
        }

        return $this;
    }

    public function getNdfDepensesAutre(): Collection
    {
        return $this->ndfDepensesAutre;
    }

    public function addNdfDepensesAutre(NdfDemande $ndfDepensesAutre): self
    {
        if (!$this->ndfDepensesAutre->contains($ndfDepensesAutre)) {
            $this->ndfDepensesAutre[] = $ndfDepensesAutre;
            $ndfDepensesAutre->setNdfDemande($this);
        }

        return $this;
    }

    public function removeNdfDepensesAutre(NdfDemande $ndfDepensesAutre): self
    {
        if ($this->ndfDepensesAutre->contains($ndfDepensesAutre)) {
            $this->ndfDepensesAutre->removeElement($ndfDepensesAutre);
            // set the owning side to null (unless already changed)
            if ($ndfDepensesAutre->getNdfDemande() === $this) {
                $ndfDepensesAutre->setNdfDemande(null);
            }
        }

        return $this;
    }
}
