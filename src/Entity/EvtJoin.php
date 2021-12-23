<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EvtJoin.
 *
 * @ORM\Table(name="caf_evt_join")
 * @ORM\Entity
 */
class EvtJoin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_evt_join", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="status_evt_join", type="smallint", nullable=false, options={"comment": "0=non confirmé - 1=validé - 2=refusé"})
     */
    private $status = '0';

    /**
     * @ORM\ManyToOne(targetEntity="Evt", inversedBy="joins", fetch="EAGER")
     * @ORM\JoinColumn(name="evt_evt_join", nullable=false, referencedColumnName="id_evt", nullable=false, onDelete="CASCADE")
     */
    private $evt;

    /**
     * @var int
     *
     * @ORM\Column(name="user_evt_join", type="integer", nullable=false)
     */
    private $user;

    /**
     * @var int
     *
     * @ORM\Column(name="affiliant_user_join", type="integer", nullable=false, options={"comment": "Si non nulle, cette valeur cible l'utilisateur qui a joint cet user via la fonction d'affiliation. C'est donc lui qui doit recevoir les emails informatifs."})
     */
    private $affiliantUserJoin;

    /**
     * @var string
     *
     * @ORM\Column(name="role_evt_join", type="string", length=20, nullable=false)
     */
    private $role;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_evt_join", type="bigint", nullable=false)
     */
    private $tsp;

    /**
     * @var int
     *
     * @ORM\Column(name="lastchange_when_evt_join", type="bigint", nullable=false, options={"comment": "Quand a été modifié cet élément"})
     */
    private $lastchangeWhen;

    /**
     * @var int
     *
     * @ORM\Column(name="lastchange_who_evt_join", type="integer", nullable=false, options={"comment": "Qui a modifié cet élément"})
     */
    private $lastchangeWho;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id_bus_lieu_destination", type="integer", nullable=true, options={"unsigned": true})
     */
    private $idBusLieuDestination;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id_destination", type="integer", nullable=true, options={"unsigned": true})
     */
    private $idDestination;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_covoiturage", type="boolean", nullable=true)
     */
    private $isCovoiturage;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_restaurant", type="boolean", nullable=true)
     */
    private $isRestaurant;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_cb", type="boolean", nullable=true)
     */
    private $isCb;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEvt(): ?Evt
    {
        return $this->evt;
    }

    public function setEvt(Evt $evt): self
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

    public function getAffiliantUserJoin(): ?int
    {
        return $this->affiliantUserJoin;
    }

    public function setAffiliantUserJoin(int $affiliantUserJoin): self
    {
        $this->affiliantUserJoin = $affiliantUserJoin;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getTsp(): ?string
    {
        return $this->tsp;
    }

    public function setTsp(string $tsp): self
    {
        $this->tsp = $tsp;

        return $this;
    }

    public function getLastchangeWhen(): ?string
    {
        return $this->lastchangeWhen;
    }

    public function setLastchangeWhen(string $lastchangeWhen): self
    {
        $this->lastchangeWhen = $lastchangeWhen;

        return $this;
    }

    public function getLastchangeWho(): ?int
    {
        return $this->lastchangeWho;
    }

    public function setLastchangeWho(int $lastchangeWho): self
    {
        $this->lastchangeWho = $lastchangeWho;

        return $this;
    }

    public function getIdBusLieuDestination(): ?int
    {
        return $this->idBusLieuDestination;
    }

    public function setIdBusLieuDestination(?int $idBusLieuDestination): self
    {
        $this->idBusLieuDestination = $idBusLieuDestination;

        return $this;
    }

    public function getIdDestination(): ?int
    {
        return $this->idDestination;
    }

    public function setIdDestination(?int $idDestination): self
    {
        $this->idDestination = $idDestination;

        return $this;
    }

    public function getIsCovoiturage(): ?bool
    {
        return $this->isCovoiturage;
    }

    public function setIsCovoiturage(?bool $isCovoiturage): self
    {
        $this->isCovoiturage = $isCovoiturage;

        return $this;
    }

    public function getIsRestaurant(): ?bool
    {
        return $this->isRestaurant;
    }

    public function setIsRestaurant(?bool $isRestaurant): self
    {
        $this->isRestaurant = $isRestaurant;

        return $this;
    }

    public function getIsCb(): ?bool
    {
        return $this->isCb;
    }

    public function setIsCb(?bool $isCb): self
    {
        $this->isCb = $isCb;

        return $this;
    }
}
