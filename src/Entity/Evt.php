<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Evt.
 *
 * @ORM\Table(name="caf_evt")
 * @ORM\Entity
 */
class Evt
{
    public const STATUS_PUBLISHED_UNSEEN = 0;
    public const STATUS_PUBLISHED_VALIDE = 1;
    public const STATUS_PUBLISHED_REFUSE = 2;

    public const STATUS_LEGAL_UNSEEN = 0;
    public const STATUS_LEGAL_VALIDE = 1;
    public const STATUS_LEGAL_REFUSE = 2;

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
     * @ORM\Column(name="status_evt", type="smallint", nullable=false, options={"comment": "0-unseen 1-ok 2-refused", "default": 0})
     */
    private $status = 0;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="status_who_evt", referencedColumnName="id_user", nullable=true)
     */
    private $statusWho;

    /**
     * @var int
     *
     * @ORM\Column(name="status_legal_evt", type="smallint", nullable=false, options={"comment": "0-unseen 1-ok 2-refused", "default": 0})
     */
    private $statusLegal = 0;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="status_legal_who_evt", referencedColumnName="id_user", nullable=true)
     */
    private $statusLegalWho;

    /**
     * @var bool
     *
     * @ORM\Column(name="cancelled_evt", type="boolean", nullable=false, options={"default": false})
     */
    private $cancelled = '0';

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="cancelled_who_evt", referencedColumnName="id_user", nullable=true)
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
     * @var Groupe|null
     *
     * @ORM\ManyToOne(targetEntity="Groupe", fetch="EAGER")
     * @ORM\JoinColumn(name="id_groupe", referencedColumnName="id", nullable=true)
     */
    private $groupe;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_evt", type="bigint", nullable=true, options={"comment": "timestamp du début du event"})
     */
    private $tsp;

    /**
     * @var int
     *
     * @ORM\Column(name="tsp_end_evt", type="bigint", nullable=true)
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
     * @ORM\Column(name="massif_evt", type="string", length=100, nullable=true)
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
     * @ORM\Column(name="matos_evt", type="text", length=65535, nullable=true)
     */
    private $matos;

    /**
     * @var string
     *
     * @ORM\Column(name="difficulte_evt", type="string", length=50, nullable=true)
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
     * @ORM\Column(name="join_start_evt", type="integer", nullable=true, options={"comment": "Timestamp de départ des inscriptions"})
     */
    private $joinStart;

    /**
     * @var int
     *
     * @ORM\Column(name="join_max_evt", type="integer", nullable=false, options={"comment": "Nombre max d'inscriptions spontanées sur le site, ET PAS d'inscrits total"})
     */
    private $joinMax;

    /**
     * @var EvtJoin[]
     *
     * @ORM\OneToMany(targetEntity="EvtJoin", mappedBy="evt", cascade={"persist"})
     */
    private $joins;

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
    private $cycleMaster = false;

    /**
     * @ORM\ManyToOne(targetEntity="Evt", inversedBy="cycleChildren")
     * @ORM\JoinColumn(name="cycle_parent_evt", referencedColumnName="id_evt", nullable=true)
     */
    private $cycleParent;

    /**
     * @ORM\OneToMany(targetEntity="Evt", mappedBy="cycleParent")
     */
    private $cycleChildren;

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
     * @ORM\OneToMany(targetEntity="App\Entity\Article", mappedBy="evt")
     */
    private $articles;

    public function __construct(
        User $user,
        Commission $commission,
        string $titre,
        string $code,
        ?\DateTime $dateStart,
        ?\DateTime $dateEnd,
        string $rdv,
        float $rdvLat,
        float $rdvLong,
        string $description,
        ?int $demarrageInscriptions,
        int $maxInscriptions,
        int $maxParticipants
    ) {
        $this->user = $user;
        $this->titre = $titre;
        $this->code = $code;
        $this->tsp = $dateStart ? $dateStart->getTimestamp() : null;
        $this->tspEnd = $dateEnd ? $dateEnd->getTimestamp() : null;
        $this->place = ''; // unused, must be dropped
        $this->rdv = $rdv;
        $this->lat = $rdvLat;
        $this->long = $rdvLong;
        $this->description = $description;
        $this->joinStart = $demarrageInscriptions;
        $this->joinMax = $maxInscriptions;
        $this->ngensMax = $maxParticipants;
        $this->commission = $commission;
        $this->joins = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->cycleChildren = new ArrayCollection();
        $this->tspCrea = time();

        // FIX ME fix encadrant
        $this->joins->add(new EvtJoin($this, $user, EvtJoin::ROLE_ENCADRANT, EvtJoin::STATUS_VALIDE));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticles(): Collection
    {
        return $this->articles;
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

    public function getStatusWho(): ?User
    {
        return $this->statusWho;
    }

    public function setStatusWho(User $statusWho): self
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

    public function getStatusLegalWho(): ?User
    {
        return $this->statusLegalWho;
    }

    public function setStatusLegalWho(User $statusLegalWho): self
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

    public function getCancelledWho(): ?User
    {
        return $this->cancelledWho;
    }

    public function setCancelledWho(?User $cancelledWho): self
    {
        $this->cancelledWho = $cancelledWho;

        return $this;
    }

    public function getCancelledWhen(): ?int
    {
        return $this->cancelledWhen;
    }

    public function setCancelledWhen(?int $cancelledWhen): self
    {
        $this->cancelledWhen = $cancelledWhen;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function addParticipant(User $user, string $role = EvtJoin::ROLE_INSCRIT, int $status = EvtJoin::STATUS_NON_CONFIRME): EvtJoin
    {
        $participant = new EvtJoin($this, $user, $role, $status);
        $this->joins->add($participant);

        return $participant;
    }

    /** @return EvtJoin[] */
    public function getParticipants($roles = null, $status = EvtJoin::STATUS_VALIDE): Collection
    {
        if (null !== $roles && !\is_array($roles)) {
            $roles = (array) $roles;
        }
        if (null !== $status && !\is_array($status)) {
            $status = (array) $status;
        }

        return $this->joins->filter(function (EvtJoin $participant) use ($roles, $status) {
            return (null === $roles || \in_array($participant->getRole(), $roles, true))
                && (null === $status || \in_array($participant->getStatus(), $status, true));
        });
    }

    public function getParticipant(?User $user): ?EvtJoin
    {
        if (!$user) {
            return null;
        }

        foreach ($this->joins as $join) {
            if ($join->getUser() === $user) {
                return $join;
            }
        }

        return null;
    }

    public function getParticipantById(int $id): ?EvtJoin
    {
        foreach ($this->joins as $join) {
            if ($join->getId() === $id) {
                return $join;
            }
        }

        return null;
    }

    /** @return EvtJoin[] */
    public function getEncadrants($types = [EvtJoin::ROLE_ENCADRANT, EvtJoin::ROLE_STAGIAIRE, EvtJoin::ROLE_COENCADRANT]): Collection
    {
        return $this->getParticipants($types, [EvtJoin::STATUS_VALIDE]);
    }

    public function getCommission(): Commission
    {
        return $this->commission;
    }

    public function setCommission(Commission $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): self
    {
        $this->groupe = $groupe;

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

    public function isPublicStatusUnseen()
    {
        return self::STATUS_PUBLISHED_UNSEEN === $this->status;
    }

    public function isPublicStatusValide()
    {
        return self::STATUS_PUBLISHED_VALIDE === $this->status;
    }

    public function isPublicStatusRefuse()
    {
        return self::STATUS_PUBLISHED_REFUSE === $this->status;
    }

    public function isLegalStatusUnseen()
    {
        return self::STATUS_LEGAL_UNSEEN === $this->statusLegal;
    }

    public function isLegalStatusValide()
    {
        return self::STATUS_LEGAL_VALIDE === $this->statusLegal;
    }

    public function isLegalStatusRefuse()
    {
        return self::STATUS_LEGAL_REFUSE === $this->statusLegal;
    }

    public function hasStarted(): bool
    {
        return $this->tsp < time();
    }

    public function startsAfter(string $when): bool
    {
        return $this->tsp > strtotime($when);
    }

    public function joinHasStarted(): bool
    {
        return $this->joinStart < time();
    }

    public function isFinished(): bool
    {
        return $this->tspEnd < time();
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

    public function getCycleParent(): ?self
    {
        return $this->cycleParent;
    }

    public function setCycleParent(self $cycleParent): self
    {
        $this->cycleParent = $cycleParent;

        return $this;
    }

    /** @return Evt[] */
    public function getCycleChildren(): Collection
    {
        return $this->cycleChildren;
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
}
