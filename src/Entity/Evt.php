<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Evt.
 *
 * @ORM\Table(name="caf_evt")
 * @ORM\Entity
 */
class Evt
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_evt", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="status_evt", type="smallint", nullable=false, options={"comment": "0-unseen 1-ok 2-refused"})
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="status_who_evt", type="integer", nullable=true, options={"comment": "ID de l'user qui a changé le statut en dernier"})
     */
    private $statusWho;

    /**
     * @var int
     *
     * @ORM\Column(name="status_legal_evt", type="smallint", nullable=false, options={"comment": "0-unseen 1-ok 2-refused"})
     */
    private $statusLegal;

    /**
     * @var int
     *
     * @ORM\Column(name="status_legal_who_evt", type="integer", nullable=true, options={"comment": "ID du validateur légal"})
     */
    private $statusLegalWho = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="cancelled_evt", type="boolean", nullable=false, options={"default": false})
     */
    private $cancelled = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="cancelled_who_evt", type="integer", nullable=true, options={"comment": "ID user qui a  annulé l'evt"})
     */
    private $cancelledWho;

    /**
     * @var int
     *
     * @ORM\Column(name="cancelled_when_evt", type="bigint", nullable=true, options={"comment": "Timestamp annulation"})
     */
    private $cancelledWhen;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_evt", referencedColumnName="id_user", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Commission")
     * @ORM\JoinColumn(name="commission_evt", referencedColumnName="id_commission", nullable=false)
     */
    private $commission;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id_groupe", type="integer", nullable=true, options={"unsigned": true})
     */
    private $idGroupe;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_evt", type="bigint", nullable=false, options={"comment": "timestamp du début du event"})
     */
    private $tsp;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_end_evt", type="bigint", nullable=false)
     */
    private $tspEnd;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_crea_evt", type="bigint", nullable=false, options={"comment": "Création de l'entrée"})
     */
    private $tspCrea;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_edit_evt", type="bigint", nullable=true)
     */
    private $tspEdit;

    /**
     * @var string
     *
     * @ORM\Column(name="place_evt", type="string", length=100, nullable=false, options={"comment": "Lieu de RDV covoiturage"})
     */
    private $place;

    /**
     * @var string
     *
     * @ORM\Column(name="titre_evt", type="string", length=100, nullable=false)
     */
    private $titre;

    /**
     * @var string
     *
     * @ORM\Column(name="code_evt", type="string", length=30, nullable=false)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="massif_evt", type="string", length=100, nullable=false)
     */
    private $massif;

    /**
     * @var string
     *
     * @ORM\Column(name="rdv_evt", type="string", length=200, nullable=false, options={"comment": "Lieu détaillé du rdv"})
     */
    private $rdv;

    /**
     * @var float|null
     *
     * @ORM\Column(name="tarif_evt", type="float", precision=10, scale=2, nullable=true)
     */
    private $tarif;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tarif_detail", type="text", length=65535, nullable=true)
     */
    private $tarifDetail;

    /**
     * @var bool
     *
     * @ORM\Column(name="repas_restaurant", type="boolean", nullable=false)
     */
    private $repasRestaurant = '0';

    /**
     * @var float|null
     *
     * @ORM\Column(name="tarif_restaurant", type="float", precision=10, scale=2, nullable=true)
     */
    private $tarifRestaurant;

    /**
     * @var int|null
     *
     * @ORM\Column(name="denivele_evt", type="integer", nullable=true, options={"unsigned": true})
     */
    private $denivele;

    /**
     * @var float|null
     *
     * @ORM\Column(name="distance_evt", type="float", precision=10, scale=2, nullable=true)
     */
    private $distance;

    /**
     * @var string
     *
     * @ORM\Column(name="lat_evt", type="decimal", precision=11, scale=8, nullable=false)
     */
    private $lat;

    /**
     * @var string
     *
     * @ORM\Column(name="long_evt", type="decimal", precision=11, scale=8, nullable=false)
     */
    private $long;

    /**
     * @var string
     *
     * @ORM\Column(name="matos_evt", type="text", length=65535, nullable=false)
     */
    private $matos;

    /**
     * @var string
     *
     * @ORM\Column(name="difficulte_evt", type="string", length=50, nullable=false)
     */
    private $difficulte;

    /**
     * @var string|null
     *
     * @ORM\Column(name="itineraire", type="text", length=65535, nullable=true)
     */
    private $itineraire;

    /**
     * @var string
     *
     * @ORM\Column(name="description_evt", type="text", length=65535, nullable=false)
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="need_benevoles_evt", type="boolean", nullable=false)
     */
    private $needBenevoles = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="join_start_evt", type="integer", nullable=false, options={"comment": "Timestamp de départ des inscriptions"})
     */
    private $joinStart;

    /**
     * @var int
     *
     * @ORM\Column(name="join_max_evt", type="integer", nullable=false, options={"comment": "Nombre max d'inscriptions spontanées sur le site, ET PAS d'inscrits total"})
     */
    private $joinMax;

    /**
     * @var int
     *
     * @ORM\Column(name="ngens_max_evt", type="integer", nullable=false, options={"comment": "Nombre de gens pouvant y aller au total. Donnée ""visuelle"" uniquement, pas de calcul."})
     */
    private $ngensMax;

    /**
     * @var bool
     *
     * @ORM\Column(name="cycle_master_evt", type="boolean", nullable=false, options={"comment": "Est-ce la première sortie d'un cycle de sorties liées ?"})
     */
    private $cycleMaster;

    /**
     * @var int
     *
     * @ORM\Column(name="cycle_parent_evt", type="integer", nullable=false, options={"comment": "Si cette sortie est l'enfant d'un cycle, l'id du parent est ici"})
     */
    private $cycleParent;

    /**
     * @var int
     *
     * @ORM\Column(name="child_version_from_evt", type="integer", nullable=false, options={"comment": "Versionning : chaque modification d-evt crée une entrée ""enfant"" de l-originale. Ce champ prend l-ID de l-original"})
     */
    private $childVersionFrom = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="child_version_tosubmit", type="boolean", nullable=false)
     */
    private $childVersionTosubmit = '0';

    /**
     * @var bool|null
     *
     * @ORM\Column(name="cb_evt", type="boolean", nullable=true)
     */
    private $cb;

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

    public function getStatusWho(): ?int
    {
        return $this->statusWho;
    }

    public function setStatusWho(int $statusWho): self
    {
        $this->statusWho = $statusWho;

        return $this;
    }

    public function getStatusLegal(): ?int
    {
        return $this->statusLegal;
    }

    public function setStatusLegal(int $statusLegal): self
    {
        $this->statusLegal = $statusLegal;

        return $this;
    }

    public function getStatusLegalWho(): ?int
    {
        return $this->statusLegalWho;
    }

    public function setStatusLegalWho(int $statusLegalWho): self
    {
        $this->statusLegalWho = $statusLegalWho;

        return $this;
    }

    public function getCancelled(): ?bool
    {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): self
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    public function getCancelledWho(): ?int
    {
        return $this->cancelledWho;
    }

    public function setCancelledWho(int $cancelledWho): self
    {
        $this->cancelledWho = $cancelledWho;

        return $this;
    }

    public function getCancelledWhen(): ?string
    {
        return $this->cancelledWhen;
    }

    public function setCancelledWhen(string $cancelledWhen): self
    {
        $this->cancelledWhen = $cancelledWhen;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getCommission(): Commission
    {
        return $this->commission;
    }

    public function getIdGroupe(): ?int
    {
        return $this->idGroupe;
    }

    public function setIdGroupe(?int $idGroupe): self
    {
        $this->idGroupe = $idGroupe;

        return $this;
    }

    public function getTsp(): ?int
    {
        return $this->tsp;
    }

    public function setTsp(int $tsp): self
    {
        $this->tsp = $tsp;

        return $this;
    }

    public function getTspEnd(): ?string
    {
        return $this->tspEnd;
    }

    public function setTspEnd(string $tspEnd): self
    {
        $this->tspEnd = $tspEnd;

        return $this;
    }

    public function getTspCrea(): ?string
    {
        return $this->tspCrea;
    }

    public function setTspCrea(string $tspCrea): self
    {
        $this->tspCrea = $tspCrea;

        return $this;
    }

    public function getTspEdit(): ?string
    {
        return $this->tspEdit;
    }

    public function setTspEdit(string $tspEdit): self
    {
        $this->tspEdit = $tspEdit;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(string $place): self
    {
        $this->place = $place;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getMassif(): ?string
    {
        return $this->massif;
    }

    public function setMassif(string $massif): self
    {
        $this->massif = $massif;

        return $this;
    }

    public function getRdv(): ?string
    {
        return $this->rdv;
    }

    public function setRdv(string $rdv): self
    {
        $this->rdv = $rdv;

        return $this;
    }

    public function getTarif(): ?float
    {
        return $this->tarif;
    }

    public function setTarif(?float $tarif): self
    {
        $this->tarif = $tarif;

        return $this;
    }

    public function getTarifDetail(): ?string
    {
        return $this->tarifDetail;
    }

    public function setTarifDetail(?string $tarifDetail): self
    {
        $this->tarifDetail = $tarifDetail;

        return $this;
    }

    public function getRepasRestaurant(): ?bool
    {
        return $this->repasRestaurant;
    }

    public function setRepasRestaurant(bool $repasRestaurant): self
    {
        $this->repasRestaurant = $repasRestaurant;

        return $this;
    }

    public function getTarifRestaurant(): ?float
    {
        return $this->tarifRestaurant;
    }

    public function setTarifRestaurant(?float $tarifRestaurant): self
    {
        $this->tarifRestaurant = $tarifRestaurant;

        return $this;
    }

    public function getDenivele(): ?int
    {
        return $this->denivele;
    }

    public function setDenivele(?int $denivele): self
    {
        $this->denivele = $denivele;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getLat(): ?string
    {
        return $this->lat;
    }

    public function setLat(string $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLong(): ?string
    {
        return $this->long;
    }

    public function setLong(string $long): self
    {
        $this->long = $long;

        return $this;
    }

    public function getMatos(): ?string
    {
        return $this->matos;
    }

    public function setMatos(string $matos): self
    {
        $this->matos = $matos;

        return $this;
    }

    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    public function setDifficulte(string $difficulte): self
    {
        $this->difficulte = $difficulte;

        return $this;
    }

    public function getItineraire(): ?string
    {
        return $this->itineraire;
    }

    public function setItineraire(?string $itineraire): self
    {
        $this->itineraire = $itineraire;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getNeedBenevoles(): ?bool
    {
        return $this->needBenevoles;
    }

    public function setNeedBenevoles(bool $needBenevoles): self
    {
        $this->needBenevoles = $needBenevoles;

        return $this;
    }

    public function getJoinStart(): ?int
    {
        return $this->joinStart;
    }

    public function setJoinStart(int $joinStart): self
    {
        $this->joinStart = $joinStart;

        return $this;
    }

    public function getJoinMax(): ?int
    {
        return $this->joinMax;
    }

    public function setJoinMax(int $joinMax): self
    {
        $this->joinMax = $joinMax;

        return $this;
    }

    public function getNgensMax(): ?int
    {
        return $this->ngensMax;
    }

    public function setNgensMax(int $ngensMax): self
    {
        $this->ngensMax = $ngensMax;

        return $this;
    }

    public function getCycleMaster(): ?bool
    {
        return $this->cycleMaster;
    }

    public function setCycleMaster(bool $cycleMaster): self
    {
        $this->cycleMaster = $cycleMaster;

        return $this;
    }

    public function getCycleParent(): ?int
    {
        return $this->cycleParent;
    }

    public function setCycleParent(int $cycleParent): self
    {
        $this->cycleParent = $cycleParent;

        return $this;
    }

    public function getChildVersionFrom(): ?int
    {
        return $this->childVersionFrom;
    }

    public function setChildVersionFrom(int $childVersionFrom): self
    {
        $this->childVersionFrom = $childVersionFrom;

        return $this;
    }

    public function getChildVersionTosubmit(): ?bool
    {
        return $this->childVersionTosubmit;
    }

    public function setChildVersionTosubmit(bool $childVersionTosubmit): self
    {
        $this->childVersionTosubmit = $childVersionTosubmit;

        return $this;
    }

    public function getCb(): ?bool
    {
        return $this->cb;
    }

    public function setCb(?bool $cb): self
    {
        $this->cb = $cb;

        return $this;
    }
}
