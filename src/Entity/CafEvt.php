<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CafEvt.
 *
 * @ORM\Table(name="caf_evt")
 * @ORM\Entity
 */
class CafEvt
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_evt", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="status_evt", type="smallint", nullable=false, options={"comment": "0-unseen 1-ok 2-refused"})
     */
    private $statusEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="status_who_evt", type="integer", nullable=true, options={"comment": "ID de l'user qui a changé le statut en dernier"})
     */
    private $statusWhoEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="status_legal_evt", type="smallint", nullable=false, options={"comment": "0-unseen 1-ok 2-refused"})
     */
    private $statusLegalEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="status_legal_who_evt", type="integer", nullable=true, options={"comment": "ID du validateur légal"})
     */
    private $statusLegalWhoEvt = '0';

    /**
     * @var bool
     *
     * @ORM\Column(name="cancelled_evt", type="boolean", nullable=false, options={"default": false})
     */
    private $cancelledEvt = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="cancelled_who_evt", type="integer", nullable=true, options={"comment": "ID user qui a  annulé l'evt"})
     */
    private $cancelledWhoEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="cancelled_when_evt", type="bigint", nullable=true, options={"comment": "Timestamp annulation"})
     */
    private $cancelledWhenEvt;

    /**
     * @ORM\ManyToOne(targetEntity="CafUser")
     * @ORM\JoinColumn(name="user_evt", referencedColumnName="id_user", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="CafCommission")
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
    private $tspEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_end_evt", type="bigint", nullable=false)
     */
    private $tspEndEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_crea_evt", type="bigint", nullable=false, options={"comment": "Création de l'entrée"})
     */
    private $tspCreaEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_edit_evt", type="bigint", nullable=true)
     */
    private $tspEditEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="place_evt", type="string", length=100, nullable=false, options={"comment": "Lieu de RDV covoiturage"})
     */
    private $placeEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="titre_evt", type="string", length=100, nullable=false)
     */
    private $titreEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="code_evt", type="string", length=30, nullable=false)
     */
    private $codeEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="massif_evt", type="string", length=100, nullable=false)
     */
    private $massifEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="rdv_evt", type="string", length=200, nullable=false, options={"comment": "Lieu détaillé du rdv"})
     */
    private $rdvEvt;

    /**
     * @var float|null
     *
     * @ORM\Column(name="tarif_evt", type="float", precision=10, scale=2, nullable=true)
     */
    private $tarifEvt;

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
    private $deniveleEvt;

    /**
     * @var float|null
     *
     * @ORM\Column(name="distance_evt", type="float", precision=10, scale=2, nullable=true)
     */
    private $distanceEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="lat_evt", type="decimal", precision=11, scale=8, nullable=false)
     */
    private $latEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="long_evt", type="decimal", precision=11, scale=8, nullable=false)
     */
    private $longEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="matos_evt", type="text", length=65535, nullable=false)
     */
    private $matosEvt;

    /**
     * @var string
     *
     * @ORM\Column(name="difficulte_evt", type="string", length=50, nullable=false)
     */
    private $difficulteEvt;

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
    private $descriptionEvt;

    /**
     * @var bool
     *
     * @ORM\Column(name="need_benevoles_evt", type="boolean", nullable=false)
     */
    private $needBenevolesEvt = '0';

    /**
     * @var int
     *
     * @ORM\Column(name="join_start_evt", type="integer", nullable=false, options={"comment": "Timestamp de départ des inscriptions"})
     */
    private $joinStartEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="join_max_evt", type="integer", nullable=false, options={"comment": "Nombre max d'inscriptions spontanées sur le site, ET PAS d'inscrits total"})
     */
    private $joinMaxEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="ngens_max_evt", type="integer", nullable=false, options={"comment": "Nombre de gens pouvant y aller au total. Donnée ""visuelle"" uniquement, pas de calcul."})
     */
    private $ngensMaxEvt;

    /**
     * @var bool
     *
     * @ORM\Column(name="cycle_master_evt", type="boolean", nullable=false, options={"comment": "Est-ce la première sortie d'un cycle de sorties liées ?"})
     */
    private $cycleMasterEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="cycle_parent_evt", type="integer", nullable=false, options={"comment": "Si cette sortie est l'enfant d'un cycle, l'id du parent est ici"})
     */
    private $cycleParentEvt;

    /**
     * @var int
     *
     * @ORM\Column(name="child_version_from_evt", type="integer", nullable=false, options={"comment": "Versionning : chaque modification d-evt crée une entrée ""enfant"" de l-originale. Ce champ prend l-ID de l-original"})
     */
    private $childVersionFromEvt = '0';

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
    private $cbEvt;

    public function getIdEvt(): ?int
    {
        return $this->idEvt;
    }

    public function getStatusEvt(): ?int
    {
        return $this->statusEvt;
    }

    public function setStatusEvt(int $statusEvt): self
    {
        $this->statusEvt = $statusEvt;

        return $this;
    }

    public function getStatusWhoEvt(): ?int
    {
        return $this->statusWhoEvt;
    }

    public function setStatusWhoEvt(int $statusWhoEvt): self
    {
        $this->statusWhoEvt = $statusWhoEvt;

        return $this;
    }

    public function getStatusLegalEvt(): ?int
    {
        return $this->statusLegalEvt;
    }

    public function setStatusLegalEvt(int $statusLegalEvt): self
    {
        $this->statusLegalEvt = $statusLegalEvt;

        return $this;
    }

    public function getStatusLegalWhoEvt(): ?int
    {
        return $this->statusLegalWhoEvt;
    }

    public function setStatusLegalWhoEvt(int $statusLegalWhoEvt): self
    {
        $this->statusLegalWhoEvt = $statusLegalWhoEvt;

        return $this;
    }

    public function getCancelledEvt(): ?bool
    {
        return $this->cancelledEvt;
    }

    public function setCancelledEvt(bool $cancelledEvt): self
    {
        $this->cancelledEvt = $cancelledEvt;

        return $this;
    }

    public function getCancelledWhoEvt(): ?int
    {
        return $this->cancelledWhoEvt;
    }

    public function setCancelledWhoEvt(int $cancelledWhoEvt): self
    {
        $this->cancelledWhoEvt = $cancelledWhoEvt;

        return $this;
    }

    public function getCancelledWhenEvt(): ?string
    {
        return $this->cancelledWhenEvt;
    }

    public function setCancelledWhenEvt(string $cancelledWhenEvt): self
    {
        $this->cancelledWhenEvt = $cancelledWhenEvt;

        return $this;
    }

    public function getUser(): ?CafUser
    {
        return $this->user;
    }

    public function getCommission(): CafCommission
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

    public function getTspEvt(): ?string
    {
        return $this->tspEvt;
    }

    public function setTspEvt(string $tspEvt): self
    {
        $this->tspEvt = $tspEvt;

        return $this;
    }

    public function getTspEndEvt(): ?string
    {
        return $this->tspEndEvt;
    }

    public function setTspEndEvt(string $tspEndEvt): self
    {
        $this->tspEndEvt = $tspEndEvt;

        return $this;
    }

    public function getTspCreaEvt(): ?string
    {
        return $this->tspCreaEvt;
    }

    public function setTspCreaEvt(string $tspCreaEvt): self
    {
        $this->tspCreaEvt = $tspCreaEvt;

        return $this;
    }

    public function getTspEditEvt(): ?string
    {
        return $this->tspEditEvt;
    }

    public function setTspEditEvt(string $tspEditEvt): self
    {
        $this->tspEditEvt = $tspEditEvt;

        return $this;
    }

    public function getPlaceEvt(): ?string
    {
        return $this->placeEvt;
    }

    public function setPlaceEvt(string $placeEvt): self
    {
        $this->placeEvt = $placeEvt;

        return $this;
    }

    public function getTitreEvt(): ?string
    {
        return $this->titreEvt;
    }

    public function setTitreEvt(string $titreEvt): self
    {
        $this->titreEvt = $titreEvt;

        return $this;
    }

    public function getCodeEvt(): ?string
    {
        return $this->codeEvt;
    }

    public function setCodeEvt(string $codeEvt): self
    {
        $this->codeEvt = $codeEvt;

        return $this;
    }

    public function getMassifEvt(): ?string
    {
        return $this->massifEvt;
    }

    public function setMassifEvt(string $massifEvt): self
    {
        $this->massifEvt = $massifEvt;

        return $this;
    }

    public function getRdvEvt(): ?string
    {
        return $this->rdvEvt;
    }

    public function setRdvEvt(string $rdvEvt): self
    {
        $this->rdvEvt = $rdvEvt;

        return $this;
    }

    public function getTarifEvt(): ?float
    {
        return $this->tarifEvt;
    }

    public function setTarifEvt(?float $tarifEvt): self
    {
        $this->tarifEvt = $tarifEvt;

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

    public function getDeniveleEvt(): ?int
    {
        return $this->deniveleEvt;
    }

    public function setDeniveleEvt(?int $deniveleEvt): self
    {
        $this->deniveleEvt = $deniveleEvt;

        return $this;
    }

    public function getDistanceEvt(): ?float
    {
        return $this->distanceEvt;
    }

    public function setDistanceEvt(?float $distanceEvt): self
    {
        $this->distanceEvt = $distanceEvt;

        return $this;
    }

    public function getLatEvt(): ?string
    {
        return $this->latEvt;
    }

    public function setLatEvt(string $latEvt): self
    {
        $this->latEvt = $latEvt;

        return $this;
    }

    public function getLongEvt(): ?string
    {
        return $this->longEvt;
    }

    public function setLongEvt(string $longEvt): self
    {
        $this->longEvt = $longEvt;

        return $this;
    }

    public function getMatosEvt(): ?string
    {
        return $this->matosEvt;
    }

    public function setMatosEvt(string $matosEvt): self
    {
        $this->matosEvt = $matosEvt;

        return $this;
    }

    public function getDifficulteEvt(): ?string
    {
        return $this->difficulteEvt;
    }

    public function setDifficulteEvt(string $difficulteEvt): self
    {
        $this->difficulteEvt = $difficulteEvt;

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

    public function getDescriptionEvt(): ?string
    {
        return $this->descriptionEvt;
    }

    public function setDescriptionEvt(string $descriptionEvt): self
    {
        $this->descriptionEvt = $descriptionEvt;

        return $this;
    }

    public function getNeedBenevolesEvt(): ?bool
    {
        return $this->needBenevolesEvt;
    }

    public function setNeedBenevolesEvt(bool $needBenevolesEvt): self
    {
        $this->needBenevolesEvt = $needBenevolesEvt;

        return $this;
    }

    public function getJoinStartEvt(): ?int
    {
        return $this->joinStartEvt;
    }

    public function setJoinStartEvt(int $joinStartEvt): self
    {
        $this->joinStartEvt = $joinStartEvt;

        return $this;
    }

    public function getJoinMaxEvt(): ?int
    {
        return $this->joinMaxEvt;
    }

    public function setJoinMaxEvt(int $joinMaxEvt): self
    {
        $this->joinMaxEvt = $joinMaxEvt;

        return $this;
    }

    public function getNgensMaxEvt(): ?int
    {
        return $this->ngensMaxEvt;
    }

    public function setNgensMaxEvt(int $ngensMaxEvt): self
    {
        $this->ngensMaxEvt = $ngensMaxEvt;

        return $this;
    }

    public function getCycleMasterEvt(): ?bool
    {
        return $this->cycleMasterEvt;
    }

    public function setCycleMasterEvt(bool $cycleMasterEvt): self
    {
        $this->cycleMasterEvt = $cycleMasterEvt;

        return $this;
    }

    public function getCycleParentEvt(): ?int
    {
        return $this->cycleParentEvt;
    }

    public function setCycleParentEvt(int $cycleParentEvt): self
    {
        $this->cycleParentEvt = $cycleParentEvt;

        return $this;
    }

    public function getChildVersionFromEvt(): ?int
    {
        return $this->childVersionFromEvt;
    }

    public function setChildVersionFromEvt(int $childVersionFromEvt): self
    {
        $this->childVersionFromEvt = $childVersionFromEvt;

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

    public function getCbEvt(): ?bool
    {
        return $this->cbEvt;
    }

    public function setCbEvt(?bool $cbEvt): self
    {
        $this->cbEvt = $cbEvt;

        return $this;
    }
}
