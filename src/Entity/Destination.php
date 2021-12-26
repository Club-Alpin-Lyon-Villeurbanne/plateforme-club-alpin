<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Destination.
 *
 * @ORM\Table(name="caf_destination")
 * @ORM\Entity
 */
class Destination
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
     * @ORM\Column(name="id_lieu", type="integer", nullable=false, options={"unsigned": true})
     */
    private $idLieu;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="id_user_who_create", referencedColumnName="id_user", nullable=false)
     */
    private $idUserWhoCreate;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="id_user_responsable", referencedColumnName="id_user", nullable=false)
     */
    private $idUserResponsable;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="id_user_adjoint", referencedColumnName="id_user", nullable=true)
     */
    private $idUserAdjoint;

    /**
     * @var bool
     *
     * @ORM\Column(name="publie", type="boolean", nullable=false)
     */
    private $publie = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="annule", type="boolean", nullable=false)
     */
    private $annule = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="mail", type="boolean", nullable=false, options={"comment": "les emails de cloture ont ils dÃ©jÃ  Ã©tÃ© envoyÃ©s ?"})
     */
    private $mail = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=100, nullable=false)
     */
    private $nom;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=100, nullable=true)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_fin", type="date", nullable=true)
     */
    private $dateFin;

    /**
     * @var float|null
     *
     * @ORM\Column(name="cout_transport", type="float", precision=10, scale=2, nullable=true)
     */
    private $coutTransport;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ign", type="text", length=65535, nullable=true)
     */
    private $ign;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="inscription_ouverture", type="datetime", nullable=true)
     */
    private $inscriptionOuverture;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="inscription_fin", type="datetime", nullable=true)
     */
    private $inscriptionFin;

    /**
     * @var bool
     *
     * @ORM\Column(name="inscription_locked", type="boolean", nullable=false)
     */
    private $inscriptionLocked = '0';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIdUserWhoCreate(): ?User
    {
        return $this->idUserWhoCreate;
    }

    public function setIdUserWhoCreate(User $idUserWhoCreate): self
    {
        $this->idUserWhoCreate = $idUserWhoCreate;

        return $this;
    }

    public function getIdUserResponsable(): User
    {
        return $this->idUserResponsable;
    }

    public function setIdUserResponsable(User $idUserResponsable): self
    {
        $this->idUserResponsable = $idUserResponsable;

        return $this;
    }

    public function getIdUserAdjoint(): ?User
    {
        return $this->idUserAdjoint;
    }

    public function setIdUserAdjoint(?User $idUserAdjoint): self
    {
        $this->idUserAdjoint = $idUserAdjoint;

        return $this;
    }

    public function getPublie(): ?bool
    {
        return (bool) $this->publie;
    }

    public function setPublie(bool $publie): self
    {
        $this->publie = $publie;

        return $this;
    }

    public function getAnnule(): ?bool
    {
        return $this->annule;
    }

    public function setAnnule(bool $annule): self
    {
        $this->annule = $annule;

        return $this;
    }

    public function getMail(): ?bool
    {
        return $this->mail;
    }

    public function setMail(bool $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getCoutTransport(): ?float
    {
        return $this->coutTransport;
    }

    public function setCoutTransport(?float $coutTransport): self
    {
        $this->coutTransport = $coutTransport;

        return $this;
    }

    public function getIgn(): ?string
    {
        return $this->ign;
    }

    public function setIgn(?string $ign): self
    {
        $this->ign = $ign;

        return $this;
    }

    public function getInscriptionOuverture(): ?\DateTimeInterface
    {
        return $this->inscriptionOuverture;
    }

    public function setInscriptionOuverture(?\DateTimeInterface $inscriptionOuverture): self
    {
        $this->inscriptionOuverture = $inscriptionOuverture;

        return $this;
    }

    public function getInscriptionFin(): ?\DateTimeInterface
    {
        return $this->inscriptionFin;
    }

    public function setInscriptionFin(?\DateTimeInterface $inscriptionFin): self
    {
        $this->inscriptionFin = $inscriptionFin;

        return $this;
    }

    public function getInscriptionLocked(): ?bool
    {
        return $this->inscriptionLocked;
    }

    public function setInscriptionLocked(bool $inscriptionLocked): self
    {
        $this->inscriptionLocked = $inscriptionLocked;

        return $this;
    }
}
