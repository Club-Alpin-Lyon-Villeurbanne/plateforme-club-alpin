<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafEvtJoin.
 *
 * @ORM\Table(name="caf_evt_join")
 * @ORM\Entity
 */
class CafEvtJoin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_evt_join", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idEvtJoin;

    /**
     * @var int
     *
     * @ORM\Column(name="status_evt_join", type="smallint", nullable=false, options={"comment": "0=non confirmé - 1=validé - 2=refusé"})
     */
    private $statusEvtJoin = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="evt_evt_join", type="integer", nullable=false)
     */
    private $evtEvtJoin;

    /**
     * @var int
     *
     * @ORM\Column(name="user_evt_join", type="integer", nullable=false)
     */
    private $userEvtJoin;

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
    private $roleEvtJoin;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_evt_join", type="bigint", nullable=false)
     */
    private $tspEvtJoin;

    /**
     * @var int
     *
     * @ORM\Column(name="lastchange_when_evt_join", type="bigint", nullable=false, options={"comment": "Quand a été modifié cet élément"})
     */
    private $lastchangeWhenEvtJoin;

    /**
     * @var int
     *
     * @ORM\Column(name="lastchange_who_evt_join", type="integer", nullable=false, options={"comment": "Qui a modifié cet élément"})
     */
    private $lastchangeWhoEvtJoin;

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

    public function getIdEvtJoin(): ?int
    {
        return $this->idEvtJoin;
    }

    public function getStatusEvtJoin(): ?int
    {
        return $this->statusEvtJoin;
    }

    public function setStatusEvtJoin(int $statusEvtJoin): self
    {
        $this->statusEvtJoin = $statusEvtJoin;

        return $this;
    }

    public function getEvtEvtJoin(): ?int
    {
        return $this->evtEvtJoin;
    }

    public function setEvtEvtJoin(int $evtEvtJoin): self
    {
        $this->evtEvtJoin = $evtEvtJoin;

        return $this;
    }

    public function getUserEvtJoin(): ?int
    {
        return $this->userEvtJoin;
    }

    public function setUserEvtJoin(int $userEvtJoin): self
    {
        $this->userEvtJoin = $userEvtJoin;

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

    public function getRoleEvtJoin(): ?string
    {
        return $this->roleEvtJoin;
    }

    public function setRoleEvtJoin(string $roleEvtJoin): self
    {
        $this->roleEvtJoin = $roleEvtJoin;

        return $this;
    }

    public function getTspEvtJoin(): ?string
    {
        return $this->tspEvtJoin;
    }

    public function setTspEvtJoin(string $tspEvtJoin): self
    {
        $this->tspEvtJoin = $tspEvtJoin;

        return $this;
    }

    public function getLastchangeWhenEvtJoin(): ?string
    {
        return $this->lastchangeWhenEvtJoin;
    }

    public function setLastchangeWhenEvtJoin(string $lastchangeWhenEvtJoin): self
    {
        $this->lastchangeWhenEvtJoin = $lastchangeWhenEvtJoin;

        return $this;
    }

    public function getLastchangeWhoEvtJoin(): ?int
    {
        return $this->lastchangeWhoEvtJoin;
    }

    public function setLastchangeWhoEvtJoin(int $lastchangeWhoEvtJoin): self
    {
        $this->lastchangeWhoEvtJoin = $lastchangeWhoEvtJoin;

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
